<?php

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(EXTENSIONS . '/extension_downloader/lib/require.php');
	require_once(TOOLKIT . '/class.xmlpage.php');
	

	class contentExtensionExtension_DownloaderExport extends XMLPage {

		public function __construct() {

			$this->_Result = new XMLElement('result');
			$this->_Result->setIncludeHeader(true);

			$this->setHttpStatus(self::HTTP_STATUS_OK);
			$this->addHeaderToPage('Content-Type', 'text/xml');
			$this->addHeaderToPage("Content-Disposition: attachment; filename=extensions-bundle-".time().".xml");

		}

		public function view(){

			$extensions = $_REQUEST['a'];
			$extensions = explode(',',$extensions);
			
			$container = new XMLElement('bundle');
			
			foreach($extensions as $extension =>$value){
				$link = new XMLElement('extension');
				$url = $value;
				$link->setAttribute('commit',$url);
				
				$container->appendChild($link);
			}

			
			$this->_Result = $container;

		}
		
	}