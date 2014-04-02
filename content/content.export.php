<?php

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(EXTENSIONS . '/extension_downloader/lib/require.php');
	

	class contentExtensionExtension_DownloaderExport extends JSONPage {
		
		public function view() {
			$extensions = $_REQUEST['a'];
			$extensions = explode(',',$extensions);
			$doc = new DOMDocument('1.0');
			$doc->formatOutput = true;
			$ext = $doc->createElement('extensions');
			$container = $doc->createElement('extension');
			
			$name = $doc->createElement('name',URL . ' Bundle');
			$container->appendChild($name);
			$version = $doc->createElement('version','1.0');
			$container->appendChild($version);
			$status = $doc->createElement('status','experimental');
			$container->appendChild($status);
			
			foreach($extensions as $extension =>$value){
				$link = $doc->createElement('link');
				$url = $value.'/zipball/master';
				$link->setAttribute('href',$url);
				
				$container->appendChild($link);
			}
			//var_dump($container);
			//die;
			$ext->appendChild($container);
			$doc->appendChild($ext);
			$orig = URL.'-bundle'.$_REQUEST['a'];
			$hash = md5($orig);
			$file = MANIFEST. '/tmp/' .$hash.'.xml';
			$xml = $doc->saveXML();
			
			//$bool = General::writeFile($file,$xml,null,'w+');
			
			$this->_Result['url'] = $xml;
			//die;
			$this->_Result['success'] = true;
		}
		private function grabBundle(){
			
		}
		
	}