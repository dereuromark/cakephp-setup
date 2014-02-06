<?php
App::uses('AppController', 'Controller');
# fix for internal routing (sticky plugin name in url)
Configure::write('Plugin.name', 'Setup');

//Inflector::rules('singular', array('rules' => array(), 'irregular' => array(), 'uninflected' => array('configuration')));
//Inflector::rules('plural', array('rules' => array(), 'irregular' => array(), 'uninflected' => array('configuration')));

/**
 */
class SetupAppController extends AppController {

	public $components = array('Session', 'Tools.Common');

	public $helpers = array('Tools.Common', 'Tools.Format', 'Tools.Datetime', 'Tools.Numeric');

	/**
	 * Dynamically enable the table for configurations if desired
	 */
	public function __construct(CakeRequest $request, CakeResponse $response) {
		parent::__construct($request, $response);

		$this->uses = array('Setup.Configuration');
		/*
		if (($table = Configure::read('Configuration.table')) !== null) {
			if ($table === false) {
				$this->uses = array();
			} else {
				$this->uses = (array)$table;
			}
		}
		*/
	}

}
