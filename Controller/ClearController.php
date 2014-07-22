<?php
App::uses('SetupAppController', 'Setup.Controller');
/**
 */
class ClearController extends SetupAppController {

	public $uses = array();

	public $components = array();

	public $ClearCache;

	public function beforeFilter() {
		parent::beforeFilter();

		if (isset($this->Auth)) {
			//$this->Auth->allow();
		}

		App::uses('ClearCacheLib', 'Setup.Lib');
		$this->ClearCache = new ClearCacheLib();
	}

/*** user ***/

	public function admin_cache() {
		$res = $this->ClearCache->run();
		die(returns($res));
		return $this->Common->autoRedirect('/');
	}

}
