<?php
	/*
	Copyight: Deux Huit Huit 2014
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/
	ini_set('xdebug.var_display_max_depth', 5);
		ini_set('xdebug.var_display_max_children', 100000);
		ini_set('xdebug.var_display_max_data', 80000);
		ini_set('max_execution_time', 200);
	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(EXTENSIONS . '/extension_downloader/lib/require.php');
	require_once(TOOLKIT . '/class.gateway.php');

	class contentExtensionExtension_DownloaderSearch extends JSONPage {

		private $query;
		private $empty;
		private $version;
		
		/**
		 *
		 * Builds the content view
		 */
		public function view() {
			try {
				$this->parseInput();
				$this->search();
				$this->_Result['success'] = true; 
			} catch (Exception $e) {
				$this->_Result['empty'] = $this->empty;
				$this->_Result['success'] = false; 
				$this->_Result['error'] = $e->getMessage();
			}
		}
		
		
		private function parseInput() {
			$query = General::sanitize($_REQUEST['q']);
			$this->empty = empty($query);
			if ($this->empty) {
				throw new Exception(__('Query cannot be empty'));
			} else {
				// do a search for this query
				$this->query = $query;
			}
			
			$this->version = Symphony::Configuration()->get('version', 'symphony');
			
			if (!isset($_REQUEST['compatible']) || $_REQUEST['compatible'] == 'true') {
				$this->compatibleVersion = $this->version;
			}
		}
		
		
		private function getBranches($url,$token=false){
			$gateway = new Gateway();
			$gateway->init($url);
			$check = $gateway::isCurlAvailable();
			//echo phpinfo();
			
			if ($check == false) {
				throw new Exception(__('Unable to perform your request. please contact your administrator (error type : Curl Not enabled)'));
			}
			if($token != false){				
				$gateway->setopt(CURLOPT_HTTPHEADER,array('Authorization: token '.$token.''));
			}
			$response = @$gateway->exec();
			return $response;
		}
		
		
		private function github($extras = '' ,$url ='',$response = ''){
				$results = array();
				$githubusername = Symphony::Configuration()->get('github-user','extension-downloader');
				$githubauthtoken = Symphony::Configuration()->get('github-token','extension-downloader');	
				
				if ($githubusername == ''){
					throw new Exception(__("Please set your Github username"));
				}
				if ($githubauthtoken == ''){
					throw new Exception(__("Please set your Github Auth Token"));
				}
				$query = Lang::createHandle(str_replace(' ','+',$this->query));
				$url = 'https://api.github.com/search/repositories?q='.$query.'+'.$extras;	
				$response = $this->getBranches($url,$githubauthtoken);
				
				$json = json_decode($response);
				
				// parse xml
				
				if (!$json) {
					throw new Exception(__("Could not parse xml"));
				}
				if ($json->message != '') {
					throw new Exception(__($json->message));
				}
				foreach($json->items as $repo){
					$name = $repo->name;
					$branchlist = $repo->branches_url;
					$branchlist = str_replace('{/branch}','',$branchlist);
					$barray = array();
					$response = json_decode($this->getBranches($branchlist,$githubauthtoken));
						
					foreach($response as $b => $e){
							if($e->name  !=''){
								$barray[] = $e->name;	
							}
					}
					if(empty($barray)){
						$branch = 'master';
					}else{
						$branch = implode(',',$barray);					
					}
					
					$id = $repo->html_url;
					$developer = $repo->owner->login;
					$version = '1.0';
					$status = 'released';
					$compatible = $repo->default_branch;
					$res = array(
						'handle' => (string)$id,
						'name' => (string)$name,
						'branches' => $branch,
						'by' => (string)$developer,
						'version' => (string)$version,
						'status' => (string)$status,
						'compatible' => ($compatible != null),
					);
					$results[] = $res;
				}
				return $results;
		}
		
		
		private function symextensions(){
				$results = array();
				$url = "http://symphonyextensions.com/api/extensions/?keywords=$this->query&type=&compatible-with=$this->compatibleVersion&sort=updated&order=desc";
				
				// create the Gateway object
				$response = $this->getBranches($url);							
				if (!$response) {
					throw new Exception(__("Could not read from %s", array($url)));
				}
				
				// parse xml
				$xml = @simplexml_load_string($response);
				
				if (!$xml) {
					throw new Exception(__("Could not parse xml from %s", array($url)));
				}
				
				$extensions = $xml->xpath('/response/extensions/extension');
				
				foreach ($extensions as $index => $ext) {
					$name = $ext->xpath('name');
					$id = $ext->xpath('@id');
					$developer = $ext->xpath('developer/name');
					$version = $ext->xpath('version');
					$status = $ext->xpath('status');
					$compatible = $ext->xpath("compatibility/symphony[@version='$this->version']");					
					$res = array(
						'handle' => (string)$id[0],
						'name' => (string)$name[0],
						'by' => (string)$developer[0],
						'branches' => 'master',
						'version' => (string)$version[0],
						'status' => (string)$status[0],
						'compatible' => ($compatible != null),
					);					
					$results[] = $res;
				}
				
				return $results;
		}
		
		
		private function search() {
			$check = General::sanitize($_REQUEST['c']);			
			if($check == 'checked'){
				$results =  $this->github();
			}else{
				$results =  $this->symextensions();			
			}		
			
			$this->_Result['results'] = $results;
		}
		
	}