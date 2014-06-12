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
		
		
		//if (is_null(ExtensionManger::$_instance)) die("<h2>Error</h2><p>You cannot directly access this file</p>");
		/**
		 * Name of the extension
		 * @var string
		 */
		//const EXT_NAME = 'Extension Downloader';
		
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
				),
				array(
					'page'=> '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'addPreferences'
				),
				array(
					'page'=> '/system/preferences/',
					'delegate' => 'save',
					'callback' => 'savePreferences'
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
					if(!is_null($wrapper)){
						/* Input filebrowser button */
						$form = new XMLElement('form');
						$form->setAttribute('method','post');
						$form->setAttribute('action',SYMPHONY_URL.'/extension/extension_downloader/upload/');
						$form->setAttribute('ENCTYPE','multipart/form-data');
						$form->setAttribute('class','wrapped-form');
						$form->setAttribute('id','upload');
						$form->setAttribute('role','form');
						$ul = new XMLElement('ul');
						$ul->setAttribute('class','actions');

						$span = new XMLElement('span','Import/Export Extensions as a Bundle');
						
						$ul->appendChild($span);
						
						/* Import Bundle */
						$importinput = Widget::Input('files','','file',array('id'=>'xml_browser'));
						$importbutton = new XMLElement('li');
						$importlink = Widget::Anchor('Import', '#', 'Import', 'button', 'import_extensions', null);
						$importbutton->appendChild($importlink);

						/*remove extension from directory*/
						$removebutton = new XMLElement('li');
						$remove = Widget::Anchor('Remove','#','Remove','button','remove_extensions',null);
						$removebutton->appendChild($remove);
						/* Export Bundle of Extensions as XML */
						$exportbutton = new XMLElement('li');
						$exportlink = Widget::Anchor('Export', '#', 'Export', 'button', 'export_extensions',null);
						$exportbutton->appendChild($exportlink);
						$ul->appendChild($importinput);
						$ul->appendChild($importbutton);
						$ul->appendChild($exportbutton);
						$ul->appendChild($removebutton);
						
						//var_dump($ul);
						//die;
						$form->appendChild($ul);
						$wrapper->appendChild($form);
					}
			}
		 }

		 function getChildren(){
				
			    $children = $this->_children;
					
				return $children;
		}
		public function addpreferences(array $context){
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Extension Downloader')));
			$fieldset->appendChild(
				new XMLElement('p', __('Provide your github details'), array('class' => 'help'))
			);

			$div = new XMLElement('div');			
			$label = new XMLElement('label', __('Username'));
			$label2 = new XMLElement('label', __('Token'));
			// Get the Sections that contain a Member field.
						
			if(Symphony::Configuration()->get('github-user','extension-downloader')){
				$githubusername = Symphony::Configuration()->get('github-user','extension-downloader');
				
			}else{
				$githubusername = '';
			}			
			if(Symphony::Configuration()->get('github-token','extension-downloader')){
				$githubauthtoken = Symphony::Configuration()->get('github-token','extension-downloader');
			}
			else{
				$githubauthtoken = '';
			}
			if($githubauthtoken != ''){
				$token = $githubauthtoken;
				$user = $githubusername;
			}else{
				$token  ='';
				$user = '';
			}
			$tokenfield = Widget::Input('settings[extension-downloader][github-token]',$token,'text');
			$userfield = Widget::Input('settings[extension-downloader][github-user]',$user,'text');
			$label->appendChild($userfield);
			$label2->appendChild($tokenfield);
			$div->appendChild($label);
			$div->appendChild($label2);
	
			$fieldset->appendChild($div);

			$context['wrapper']->appendChild($fieldset);
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
		public function savePreferences(array $context){
			
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