<?php
	ini_set('xdebug.var_display_max_depth', 500);
	ini_set('xdebug.var_display_max_children', 2048);
	ini_set('xdebug.var_display_max_data', 28186);
	ini_set('max_execution_time', 200);
	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(EXTENSIONS . '/extension_downloader/lib/require.php');
	require_once(TOOLKIT . '/class.gateway.php');

	class contentExtensionExtension_DownloaderUpload extends JSONPage {
		private $forceOverwrite;
		private $alreadyExists;
		private $downloadUrl;
		private $extensionHandle;
		
		private function getDestinationDirectory() {
			return EXTENSIONS . '/' . $this->extensionHandle;	
		}
		public function view() {
				try {
						$error = array();
						
						$uploaddir = MANIFEST . '/tmp/';
						// looping over REQUEST files to extract urls
						foreach($_FILES as $file){
							// on success of uploaded file and move to '/tmp/' 
							if(move_uploaded_file($file['tmp_name'], $uploaddir .basename($file['name']))){
								$tmpfile = $uploaddir .$file['name'];								
								
								// load xml file 
								$sxe = simplexml_load_file($tmpfile);
								
								// grab first urls extension
								
								$extension = $sxe->extension->attributes('commit');							
								$path_parts = pathinfo($extension);	
								
								// testing to see if first url is an XML file
								if($path_parts['extension'] == 'xml'){	
									// performs readextension function to loop over XML files inside, used for multiple bundles
									$this->readExtension($sxe,$tmpfile);
								}
								else{								
									// if first url isn't an xml file  grab repos from directory
									$this->readBundle($tmpfile);
								}							
								
								$error[] = false;
							}
							else{
								
								$error[] = true;							
								
							}
							
						}
						if($error[0] == false){						
							$this->_Result['success'] = true;						
						}else{
							$this->_Result['error'] = true;						
						}
					}catch (Exception $e) {
							$this->_Result['empty'] = $this->empty;
							$this->_Result['success'] = false; 
							$this->_Result['error'] = $e->getMessage();
					}				
					// checks if error occured during move to '/tmp/' file and return success when error false
					
		}
		public function readExtension($file,$tmpfile){
			// loops over each url inside multiple bundle file
			// e.g. 
			// <bundle>
			//	<extension commit="https://raw.githubusercontent.com/orchard-studio/orchard-bundle/master/multilingual-bundle.xml"/>
			//	<extension commit="https://raw.githubusercontent.com/orchard-studio/orchard-bundle/master/symphony-default-bundle.xml"/>
			// </bundle>
			foreach($file as $extension){
				$commit = (string) $extension->attributes('commit');
				$path_parts = pathinfo($commit);				
				
				// grabs extension commit attribute value extension and checks on each if it an xml file
				if($path_parts['extension'] == 'xml'){
						
					$this->readBundle($commit);
				}														
			}
		}
		public function readBundle($dir){
			// reads the single xml files as and grabs the repos from each of them 
			$sxe = simplexml_load_file($dir); 
			// e.g.
			// <bundle>
			//	<extension commit="https://github.com/vlad-ghita/page_lhandles"/>
			//	<extension commit="https://github.com/vlad-ghita/languages"/>
			//	<extension commit="https://github.com/vlad-ghita/frontend_localisation"/>
			//	<extension commit="https://github.com/vlad-ghita/multilingual_field"/>
			//	<extension commit="https://github.com/DeuxHuitHuit/flang_redirection"/>
			//	<extension commit="https://github.com/symphonists/publish_tabs"/>
			//	<extension commit="https://github.com/rowan-lewis/textboxfield"/>
			// </bundle>
				foreach($sxe as $links){
					$href = (string) $links->attributes('commit');
					// performs getrepos function that pulls down each repo as a zipball
					$this->getRepos($href);					
				}
		}		
		private function getRepos($url){	

			// Using symphonys gateway curl request methods to retrieve zipballs
			$gateway = new Gateway();
			
			$link = (string) rtrim($url,'/');
			$file_headers = @get_headers($link);
			
			if(!strpos($file_headers[19],200)){
				$link = explode('/',$link);
				var_dump($link);
				if($link[4] != 'extension_downloader'){
						
						$http = $link[0];					
						$domain = $link[2];
						$user = $link[3];
						$repo = $link[4];
						$tree = $link[5];
						$branch = $link[6];
						
						unset($link);
					if(isset($branch)){						
						$url = $http.'//'.$domain.'/'.$user.'/'.$repo.'/zipball/'.$branch;											
						$link = (string) rtrim($url,'/');
					}elseif(isset($tree)){
						$url = $http.'//'.$domain.'/'.$user.'/'.$repo.'/zipball/master';	
						$link = (string) rtrim($url,'/');
					}else{
						$url = $http.'//'.$domain.'/'.$user.'/'.$repo.'/zipball/master';
						$link = (string) rtrim($url,'/');						
					}
				}else{
					throw new Exception(__("Cannot download extension downloader as part of the bundle "));
				}
				
			}
			// stripping away last ending slash
			
			
			$gateway->init($link);				
			$response = @$gateway->exec();
			
			// grabbing the filehandle from the url
			$parts = explode('/', $link);			
			$handle = $parts[4];			
			$this->extensionHandle = $handle;
			$tmpFile = MANIFEST . '/tmp/' . Lang::createHandle($this->extensionHandle);
			
			
			// testing for no response from url		
			if (!$response) {
				throw new Exception(__("Could not read from %s", array($url)));
			}
			
			// testing for succession on file write method
			if (!General::writeFile($tmpFile, $response)) {
				throw new Exception(__("Could not write file to %s",array($tmpfile)));
			}
			
			// after file write succesion unzip current zipball using symphonys core ZipArchive class
			$zip = new ZipArchive();
			
			// testing for succesion on open function , once open remove file from tmp directory			
			if (!$zip->open($tmpFile)) {
				General::deleteFile($tmpFile, true); 
				throw new Exception(__("Could not open downloaded file. -- ".$tmpfile));
			}
			
			
			
			// grab directory name from zip archive, and extract to EXTENSIONS directory
			$dirname = $zip->getNameIndex(0);
			$zip->extractTo(EXTENSIONS);
			$zip->close();
			
			// once extracted remove file from 'MANIFEST/tmp/' directory
 			General::deleteFile($tmpFile, false); 
			$curDir = EXTENSIONS . '/' . $dirname;
			$toDir = $this->getDestinationDirectory();
			
			// throw exception if delete method fails
			if (!General::deleteDirectory($toDir)) {
				throw new Exception(__('Could not delete %s', array($curDir)));
			}
			if (!@rename($curDir, $toDir)) {
				throw new Exception(__('Could not rename %s to %s', array($curDir, $toDir)));
			}
		}		
	}
