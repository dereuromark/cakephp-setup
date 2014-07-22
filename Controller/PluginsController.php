<?php
App::uses('SetupAppController', 'Setup.Controller');
/**
 */
class PluginsController extends SetupAppController {

	public $uses = array();

	public function admin_index() {
		$plugins = Configure::listObjects('plugin');
		$this->set('plugins', $plugins);
	}

	public function admin_view($pluginName) {
		$readme = APP . 'plugins' . DS . $pluginName . DS . 'README';
		if (!file_exists($readme)) {
			return $this->Common->autoRedirect(array('action' => 'index'));
		}
		$readme = file_get_contents($readme);
		$this->set('readme', $readme);
		$this->set('pluginName', Inflector::humanize($pluginName));
	}

}
