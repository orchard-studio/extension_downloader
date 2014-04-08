<?php
	ini_set('xdebug.var_display_max_depth', 500);
	ini_set('xdebug.var_display_max_children', 2048);
	ini_set('xdebug.var_display_max_data', 28186);
	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(EXTENSIONS . '/extension_downloader/lib/require.php');
	

	class contentExtensionExtension_DownloaderUpload extends JSONPage {
		private $forceOverwrite;
		private $alreadyExists;
		private $downloadUrl;
		private $extensionHandle;
		
		private function getDestinationDirectory() {
			return EXTENSIONS . '/' . $this->extensionHandle;	
		}
		public function view() {									
					$error = [];
					$uploaddir = MANIFEST . '/tmp/';
					foreach($_FILES as $file){
						if(move_uploaded_file($file['tmp_name'], $uploaddir .basename($file['name']))){
							$tmpfile = $uploaddir .$file['name'];								
							$sxe = simplexml_load_file($tmpfile);
							$extension = $sxe->extension->attributes()['commit'];							
							$path_parts = pathinfo($extension);							
							if($path_parts['extension'] == 'xml'){								
								$this->readExtension($sxe,$tmpfile);
							}
							else{								
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
		}
		public function readExtension($file,$tmpfile){
			foreach($file as $extension){
				$commit = (string) $extension->attributes()['commit'];
				$path_parts = pathinfo($commit);				
				if($path_parts['extension'] == 'xml'){
					$this->readBundle($commit);
				}														
			}
		}
		public function readBundle($dir){
			$sxe = simplexml_load_file($dir); 
				foreach($sxe as $links){
					$href = (string) $links->attributes()['commit'];
					$this->getRepos($href);					
				}
		}		
		private function getRepos($url){	

			// Using symphonys gateway curl request methods to retrieve zipballs
			$gateway = new Gateway();
			
			// stripping away last ending slash
			$link = (string) rtrim($url,'/') . '/zipball/master';
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
				throw new Exception(__("Could not write file."));
			}
			
			// after file write succesion unzip current zipball using symphonys core ZipArchive class
			$zip = new ZipArchive();
			
			// testing for succesion on open function , once open remove file from tmp directory			
			if (!$zip->open($tmpFile)) {
				General::deleteFile($tmpFile, true); 
				throw new Exception(__("Could not open downloaded file."));
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
				throw new Exception(__('Could not delete %s', array($toDir)));
			}
			if (!@rename($curDir, $toDir)) {
				throw new Exception(__('Could not rename %s to %s', array($curDir, $toDir)));
			}
		}		
	}