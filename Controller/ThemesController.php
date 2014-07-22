<?php
App::uses('SetupAppController', 'Setup.Controller');
/**
 */
class ThemesController extends SetupAppController {

	public $uses = array();

	public function admin_index() {
		$themes = Configure::listObjects('theme');

		$this->set(compact('themes'));
	}

}
