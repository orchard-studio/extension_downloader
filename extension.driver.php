<?php
	/*
	Copyight: Deux Huit Huit 2014
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");


	/**
	 *
	 * @author Deux Huit Huit
	 * http://www.deuxhuithuit.com
	 *
	 */
	class extension_extension_downloader extends Extension {

		/**
		 * Name of the extension
		 * @var string
		 */
		const EXT_NAME = 'Extension Downloader';
		
		/**
		 * Symphony utility function that permits to
		 * implement the Observer/Observable pattern.
		 * We register here delegate that will be fired by Symphony
		 */

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'appendAssets'
					
				),
				array(
					'page'=> '/backend/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => 'listBundles'
				)
			);
		}

		/**
		 *
		 * Appends javascript file referneces into the head, if needed
		 * @param array $context
		 */
		 public function listBundles(array $context){
			$page = Administration::instance()->getPageCallback();
			if($page['driver'] == 'systemextensions') {					
					$body =  $context['oPage'];
					$wrapper =  $body->Context;
					$workspace = WORKSPACE . 'bundles';
					$options = General::listStructure(WORKSPACE. '/bundles');
					$options = $options['filelist'];
					$alloptions = [];
					foreach($options as $keys => $option){
						$attr = array();
						$attr[0] = $option;
						$attr[1] = false;
						$arrs = explode('/',$option);						
						$attr[2] = end($arrs);												
						$alloptions[] = $attr;
					}
					
					$div = new XMLElement('div','Select Your Local XML File');
					
					$select = Widget::Select('bundle_extensions',$alloptions,array('id'=>'xml_file','class'=>'extension-bundle'));
					$div->setAttribute('id','Extension_Downloader');
					$div->appendChild($select);
					$wrapper->appendChild($div);
					/*Export button*/
					$contents = $body->Contents;
					
					$export = new XMLElement('div','Export Extensions as a Bundle');
					$exportinput = new XMLElement('button','Export',array('id'=>'export_extensions'));
					$export->appendChild($exportinput);
					$contents->appendChild($export);
					//var_dump($contents);
					//die;
			}
		 }
		 function getChildren() {		   
		   //foreach($this as $key => $value) {
				
			    $children = $this->_children;
				//array_push($children,$div);
					
				return $children;
		  // }
		}
		public function appendAssets(array $context) {
			// store de callback array localy
			$c = Administration::instance()->getPageCallback();
				
			// extension page
			if($c['driver'] == 'systemextensions') {

				Administration::instance()->Page->addStylesheetToHead(
					URL . '/extensions/extension_downloader/assets/extension_downloader.css',
					'screen',
					time() + 1,
					false
				);
				Administration::instance()->Page->addScriptToHead(
					URL . '/extensions/extension_downloader/assets/extension_downloader.js',
					time(),
					false
				);

				return;
			}
		}

		/* ********* INSTALL/UPDATE/UNISTALL ******* */

		/**
		 * Creates the table needed for the settings of the field
		 */
		public function install() {
			return true;

		}

		/**
		 * Creates the table needed for the settings of the field
		 */
		public function update($previousVersion) {
			return true;
		}

		/**
		 *
		 * Drops the table needed for the settings of the field
		 */
		public function uninstall() {
			return true;
		}

	}