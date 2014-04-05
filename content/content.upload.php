<?php

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
					$files = $_REQUEST['files'];
					
					$error = [];
					$uploaddir = MANIFEST . '/tmp/';
					foreach($_FILES as $file)
					{
						//var_dump($file);
						//die;
						
						if(move_uploaded_file($file['tmp_name'], $uploaddir .basename($file['name'])))
						{
							$tmpfile = $uploaddir .$file['name'];	
							
							$sxe = simplexml_load_file($tmpfile);
							$commit = (string) $sxe->attributes()['commit'];
							$path_parts = pathinfo($commit);
							if($path_parts['extension'] == 'xml'){
								$this->readBundle($tmpfile);
							}else{
								$this->readDirectoryXML($tmpfile);
							}
							$error[] = false;
						}
						else
						{
							$error[] = true;
							
						}
					}	
					if($error[0] == false){
							$this->_Result['files'] = $_FILES[0]['name'];
							//$this->_Result['success'] = true;						
					}else{
						//$this->_Result['error'] = true;
						//$this->_Result['files'] = $files;
					}
					//$this->_Result['files'] = $files;
		}
		public function readBundle($dir){
			$sxe = simplexml_load_file($dir);
				
				foreach($sxe as $links){
					$href = (string) $links->attributes()['commit'];
					$this->readDirectoryXML($href);
				}
		}
		public function readDirectoryXML($dir){
			$sxe = simplexml_load_file($dir);	
			
				
				foreach($sxe->extension as $links){

						$href = (string) $links->attributes()['commit'];
						$this->getRepos($href);
				}			
			

		}
		private function getRepos($url){			
			$gateway = new Gateway();
			$link = (string) rtrim($url,'/') . '/zipball/master';
			$gateway->init($link);				
			$response = @$gateway->exec();
			$parts = explode('/', $link);			
			$handle = $parts[4];
			
			$this->extensionHandle = $handle;			
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
	}