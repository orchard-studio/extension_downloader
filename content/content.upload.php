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
			$extensions = $_REQUEST['a'];
			$extensions = explode('!',$extensions);			
					foreach($extensions as $x => $l){									
							$this->getRepos($l);							
					}
			$this->_Result['success'] = true;	
		}
		private function getRepos($url){			
			$gateway = new Gateway();
			$link = (string) $url . 'zipball/master/';
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
			$parts = explode('/', $path);			
			$handle = $parts[4];//$parts[count($parts)-3];
			//$parts = explode('.', $handle);			
			return $handle;//$parts[count($parts)-1];
		}
	}