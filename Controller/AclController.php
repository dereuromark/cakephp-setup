<?php
App::uses('SetupAppController', 'Setup.Controller');

/**
 */
class AclController extends SetupAppController {

	public $DebugLib = null;

	public function beforeFilter() {
		parent::beforeFilter();

		if (isset($this->Auth)) {
			$this->Auth->allow();
		}
	}

/*** user ***/

	public function admin_index() {
		$this->Aco = ClassRegistry::init('Aco');
		//$this->Aco->Behaviors->load('Tree');
		$this->Aco->displayField = 'alias';
		$options = array(
			//threaded
			//'contain' => array(),
			//'limit'=>100,
			//'order' => array('lft' => 'ASC'),
		);
		$acos = $this->Aco->find('threaded', $options);
		//$acos = $this->Aco->children(1);
		//$acos = $this->Aco->generateTreeList();

		$this->set(compact('acos'));
	}

}
