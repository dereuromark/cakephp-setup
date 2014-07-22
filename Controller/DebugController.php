<?php
App::uses('SetupAppController', 'Setup.Controller');
class DebugController extends SetupAppController {

	public $uses = array();

	public function beforeFilter() {
		parent::beforeFilter();

		$debug = (int)Configure::read('debug');

		if ($debug > 0 && isset($this->Auth)) {
			$this->Auth->allow();
		}
	}

/****************************************************************************************
 * USER functions
 ****************************************************************************************/

	public function tab() {
		Configure::write('debug', 0);

		if (!empty($this->request->params['named']['tab'])) {
			$tab = $this->request->params['named']['tab'];
			//App::uses('Sanitize', 'Utility');
			//$tab = Sanitize::paranoid($tab, array('-', '_'));
			$this->Session->write('Debug.tab', $tab);
		}
		$this->autoRender = false;
	}

	public function admin_tab() {
		$this->tab();
	}

}
