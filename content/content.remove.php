<?php

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(EXTENSIONS . '/extension_downloader/lib/require.php');
	

	class contentExtensionExtension_DownloaderRemove extends JSONPage {

		
		public function view(){
			$extensions = $_REQUEST['remove'];
			$extensions = explode(',', $extensions);
			foreach($extensions as $extension => $ext){
				$url = EXTENSIONS . '/' . $ext;
				$bool  = General::deleteDirectory($url);
			}
			$this->_Result['success'] = true;
		}		
	}