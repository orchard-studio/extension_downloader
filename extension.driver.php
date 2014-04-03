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
					$body = $context['oPage'];
					$wrapper = $body->Context;
					
					/* Input filebrowser button */
					$ul = new XMLElement('ul');
					$ul->setAttribute('class','actions');

					$span = new XMLElement('span','Import/Export Extensions as a Bundle');
					
					$ul->appendChild($span);
					
					/* Import Bundle */
					$importinput = Widget::Input('','','file',array('id'=>'xml_browser'));
					$importbutton = new XMLElement('li');
					$importlink = Widget::Anchor('Import', '#', 'Import', 'button', 'import_extensions', null);
					$importbutton->appendChild($importlink);

					/* Export Bundle of Extensions as XML */
					$exportbutton = new XMLElement('li');
					$exportlink = Widget::Anchor('Export', '#', 'Export', 'button', 'export_extensions',null);
					$exportbutton->appendChild($exportlink);
					
					$ul->appendChild($importinput);
					$ul->appendChild($importbutton);
					$ul->appendChild($exportbutton);		

					$wrapper->appendChild($ul);

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