<?php
	/*
	Copyight: Deux Huit Huit 2014
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(EXTENSIONS . '/extension_downloader/lib/require.php');
	require_once(TOOLKIT . '/class.gateway.php');

	class contentExtensionExtension_DownloaderDownload extends JSONPage {

		private $forceOverwrite;
		private $alreadyExists;
		private $downloadUrl;
		private $extensionHandle;
		
		private function getDestinationDirectory() {
			return EXTENSIONS . '/' . $this->extensionHandle;	
		}
		
		/**
		 *
		 * Builds the content view
		 */
		public function view() {
			try {
				if(strpos($_REQUEST['q'],'bundle')){				
					$this->grabBundle();
					$this->_Result['success'] = true;					
				}else{
					$this->parseInput();
					$this->download();
					$this->_Result['success'] = true;
				}
			} catch (Exception $e) {
				$this->_Result['success'] = false; 				
				$this->_Result['error'] = $e->getMessage();
			}
			$this->_Result['handle'] = $this->extensionHandle; 
			$this->_Result['exists'] = $this->alreadyExists;
			$this->_Result['force'] = $this->forceOverwrite;
		}
		private function grabBundle(){
			$bundlenamerepo = $_REQUEST['q'];	
			// was "https://raw.githubusercontent.com/".$user."/".$bundlenamerepo."/master/bundle.xml";			
			$response = file_get_contents($bundlenamerepo);			
			$xml = @simplexml_load_string($response);		
			foreach($xml as $x => $l){
					$url = $l->link;
					$count = count($url);
					if($count > 1){
						foreach($url as $links => $zips){
							$urls = (string) $zips['href'];						
							$this->getRepos($urls);
						}
					}else{
							$urls = (string) $url['href'];
							$this->getRepos($urls);								
					}				
			}			
		}
		private function getRepos($url){			
			$gateway = new Gateway();
			$link = (string) $url;
			$gateway->init($link);			
			$response = @$gateway->exec();			
			$this->extensionHandle = self::handleFromURL($link);			
			$tmpFile = MANIFEST . '/tmp/' . Lang::createHandle($this->extensionHandle);
			if (!$response) {
				throw new Exception(__("Could not read from %s", array($url)));
			}
			if (!General::writeFile($tmpFile, $response)) {
				throw new Exception(__("Could not write file."));
			}			
			$zip = new ZipArchive();
			if (!$zip->open($tmpFile)) {
				General::deleteFile($tmpFile, true); 
				throw new Exception(__("Could not open downloaded file."));
			}
			$dirname = $zip->getNameIndex(0);
			$zip->extractTo(EXTENSIONS);
			$zip->close();
			General::deleteFile($tmpFile, false); 
			$curDir = EXTENSIONS . '/' . $dirname;
			$toDir = $this->getDestinationDirectory();
			if (!General::deleteDirectory($toDir)) {
				throw new Exception(__('Could not delete %s', array($toDir)));
			}
			if (!@rename($curDir, $toDir)) {
				throw new Exception(__('Could not rename %s to %s', array($curDir, $toDir)));
			}
		}
		private static function handleFromURL($path) {
			$path = str_replace('/zipball/integration', '', $path);
			$parts = explode('/', $path);			
			$handle = $parts[4];//$parts[count($parts)-3];
			//$parts = explode('.', $handle);			
			return $handle;//$parts[count($parts)-1];
		}
		private static function handleFromPath($path) {
			$path = str_replace('/zipball/integration', '', $path);
			$parts = explode('/', $path);
			$handle = $parts[count($parts)-1];
			$parts = explode('.', $handle);
			return $parts[count($parts)-1];
		}		
		private function parseInput() {
			$query = General::sanitize($_REQUEST['q']);			
			$this->forceOverwrite = (isset($_REQUEST['force']) && General::sanitize($_REQUEST['force']) == 'true');			
			if (empty($query)) {
				throw new Exception(__('Query cannot be empty'));
			} else if (strpos($query, 'zipball') !== FALSE || strpos($query, '.zip') !== FALSE) {
				$this->downloadUrl = $query;
				$this->extensionHandle = self::handleFromPath($query);
			} else if (strpos($query, '/') !== FALSE) {
				$this->extensionHandle = self::handleFromPath($query);				
				$this->downloadUrl = "https://github.com/$query/zipball/integration";
			} else {
				$this->searchExtension($query);
			}			
			$this->alreadyExists = file_exists($this->getDestinationDirectory());			
			if (!$this->forceOverwrite && $this->alreadyExists) {
				throw new Exception(__('Extension %s already exists', array($this->extensionHandle)));
			}
		}
		
		private function download() {
			$gateway = new Gateway();
			$gateway->init($this->downloadUrl);
			$response = @$gateway->exec();			
			if (!$response) {
				throw new Exception(__("Could not read from %s", array($this->downloadUrl)));
			}			
			// write the output
			$tmpFile = MANIFEST . '/tmp/' . Lang::createHandle($this->extensionHandle);			
			if (!General::writeFile($tmpFile, $response)) {
				throw new Exception(__("Could not write file."));
			}			
			// open the zip
			$zip = new ZipArchive();
			if (!$zip->open($tmpFile)) {
				General::deleteFile($tmpFile, true); 
				throw new Exception(__("Could not open downloaded file."));
			}
			// get the directory name
			$dirname = $zip->getNameIndex(0);			
			// extract
			$zip->extractTo(EXTENSIONS);
			$zip->close();			
			// delete tarbal
			General::deleteFile($tmpFile, false); 			
			// prepare
			$curDir = EXTENSIONS . '/' . $dirname;
			$toDir = $this->getDestinationDirectory();			
			// delete current version
			if (!General::deleteDirectory($toDir)) {
				throw new Exception(__('Could not delete %s', array($toDir)));
			}			
			// rename extension folder
			if (!@rename($curDir, $toDir)) {
				throw new Exception(__('Could not rename %s to %s', array($curDir, $toDir)));
			}
		}
		
		private function searchExtension($query) {				
			$url = "http://symphonyextensions.com/api/extensions/$query/";		
			// create the Gateway object
			$gateway = new Gateway();
			// set our url
			$gateway->init($url);
			// get the raw response, ignore errors
			$response = @$gateway->exec();			
			if (!$response) {
				throw new Exception(__("Could not read from %s", array($url)));
			}			
			// parse xml
			$xml = @simplexml_load_string($response);			
			if (!$xml) {
				throw new Exception(__("Could not parse xml from %s", array($url)));
			}			
			$extension = $xml->xpath('/response/extension');			
			if (empty($extension)) {
				throw new Exception(__("Could not find extension %s", array($query)));
			}
			$this->extensionHandle = $xml->xpath('/response/extension/@id');
			if (empty($this->extensionHandle)) {
				throw new Exception(__("Could not find extension handle"));
			} else {
				$this->extensionHandle = (string)$this->extensionHandle[0];
			}			
			$this->downloadUrl = $xml->xpath("/response/extension/link[@rel='github:zip']/@href");			
			if (empty($this->downloadUrl)) {
				throw new Exception(__("Could not find extension handle"));
			} else {
				$this->downloadUrl = (string)$this->downloadUrl[0];
			}
		}
	}