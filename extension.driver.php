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
					
					/* Input filebrowser button */
					$div = new XMLElement('div');
					$div->setAttribute('id','browse_xml');
					$div->setAttribute('class','options');
					$importinput = Widget::Input('','','file',array('id'=>'xml_browser'));
					$importbutton = new XMLElement('button', 'Import' );
					$importbutton->setAttribute('id','xml_browse');
					$div->appendChild($importinput);
					$div->appendChild($importbutton);					
					$wrapper->appendChild($div);
					
					/* Export Bundle of Extensions as XML */
					$exportbutton = new XMLElement('div','Import/Export Extensions as a Bundle');
					$exportbutton->setAttribute('id','export_xml');
					$exportbutton->setAttribute('class','options');
					$exportinput = new XMLElement('button','Export',array('id'=>'export_extensions'));
					$export->appendChild($exportinput);
					$wrapper->appendChild($exportbutton);

			}
		 }

		 function getChildren(){
				
			    $children = $this->_children;
					
				return $children;
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