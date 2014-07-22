<?php
App::uses('SetupAppController', 'Setup.Controller');

/**
 */
class SettingsController extends SetupAppController {

	//public $uses = array('Setup.Setting');
	public $components = array('Session');

	public function __construct() {
		parent::__construct();

		if (!isset($this->Setting)) {
			$this->Setting = ClassRegistry::init('Setup.Setting');
		}
	}

	public function beforeFilter() {
		parent::beforeFilter();
	}

/*** user ***/

	public function admin_index() {
		$settings = $this->Setting->find('all');

		if ($this->Setting->requireDefaults) {
			$keys = array_keys((array)Configure::read('DefaultSetting'));
			$keys = array_combine($keys, $keys);
			$this->set(compact('keys'));
		}
		$this->set(compact('settings'));
	}

	public function admin_add($key = null) {
		if (!empty($this->request->data)) {
			$this->Setting->create();
			if ($this->Setting->save($this->request->data)) {
				$this->Common->flashMessage(__('The Setting has been saved'), 'success');
				return $this->redirect(array('action' => 'index'));
			}
			$this->Common->flashMessage(__('The Setting could not be saved. Please, try again.'), 'error');

		} else {
			$this->request->data['Setting']['key'] = $key;
		}

		if ($this->Setting->requireDefaults) {
			$keys = array_keys((array)Configure::read('DefaultSetting'));
			$keys = array_combine($keys, $keys);
			$this->set(compact('keys'));
		}
	}

	public function admin_edit($id = null) {
		if (!($setting = $this->Setting->get($id))) {
			$this->Common->flashMessage(__('Invalid id for Setting'), 'error');
			return $this->redirect(array('action' => 'index'));
		}

		if (!empty($this->request->data)) {
			$this->Setting->id = $setting['Setting']['id'];
			if ($this->Setting->save($this->request->data)) {
				$this->Common->flashMessage(__('The Setting has been saved'), 'success');
				return $this->redirect(array('action' => 'index'));
			}
			$this->Common->flashMessage(__('The Setting could not be saved. Please, try again.'), 'error');

		} else {
			$this->request->data = $setting;
		}

		if ($this->Setting->requireDefaults) {
			$keys = array_keys((array)Configure::read('DefaultSetting'));
			$keys = array_combine($keys, $keys);
			$this->set(compact('keys'));
		}
	}

	public function admin_delete($id = null) {
		if (!$id) {
			$this->Common->flashMessage(__('Invalid id for Setting'), 'error');
			return $this->redirect(array('action' => 'index'));
		}
		if ($this->Setting->delete($id)) {
			$this->Common->flashMessage(__('Setting deleted'), 'success');
		}
		return $this->redirect(array('action' => 'index'));
	}

}
