<?php
App::uses('SetupAppController', 'Setup.Controller');

/**
 */
class UtilsController extends SetupAppController {

	public $components = array('Cookie');

	public $DebugLib = null;

	public function beforeFilter() {
		parent::beforeFilter();

		App::uses('DebugLib', 'Setup.Lib');
		$this->DebugLib = new DebugLib();

		if (isset($this->Auth)) {
			$this->Auth->allow('admin_benchmark');
		}
	}

/*** user ***/

	public function admin_index() {
	}

	public function admin_seo() {
	}

	public function admin_time() {

		if (!empty($this->request->data['Form']['timestamp'])) {
			//$this->set(compact('time'));
		}
	}

	public function admin_benchmark() {
	}

	public function admin_chars() {

		if (!empty($this->request->data['Form']['content'])) {
			App::uses('TextLib', 'Tools.Utility');
			$Text = new TextLib(null);
			$result = $Text->convertToOrdTable($this->request->data['Form']['content']);
			$this->set(compact('result'));
		}
	}

	/**
	 * IP lookup
	 */
	public function admin_geo() {
		App::uses('GeolocateLib', 'Tools.Lib');
		//$this->Common->flashMessage('Tools.GeoLocateLib not available', 'error');
		//$this->Common->autoRedirect(array('action'=>'index'));
		$this->GeolocateLib = new GeolocateLib();

		$ip = null;
		if (!empty($this->request->data['Form']['ip'])) {
			$ip = $this->request->data['Form']['ip'];
		} elseif (!empty($this->request->params['named']['ip'])) {
			$ip = $this->request->params['named']['ip'];
		}

		if (!$this->GeolocateLib->locate($ip)) {
			$this->Common->flashMessage('There is a problem with the IP Address GeoLocating', 'warning');
		} else {
			$geoValues = $this->GeolocateLib->getResult();
			$nearbyPlaces = $this->GeolocateLib->nearby();
		}
		if (empty($ip)) {
			$ip = 'own ip';
		}
		$this->set(compact('ip', 'geoValues', 'nearbyPlaces'));
	}

}
