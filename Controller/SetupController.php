<?php
App::uses('SetupAppController', 'Setup.Controller');
/**
 * Helping stuff for setting up website
 */
class SetupController extends SetupAppController {

	public $uses = array();

	public function beforeFilter() {
		parent::beforeFilter();

		if (isset($this->Auth)) {
			$this->Auth->allow('index', 'admin_coding_standards', 'admin_xhtml_elements', 'admin_form_elements', 'admin_flash_messages');
		}
	}

	/**
	 * Link to main overview
	 */
	public function index() {
	}

	/**
	 * Main overview
	 */
	public function admin_index() {
	}

/* resources */

	public function admin_coding_standards() {
		$this->helpers[] = 'Tools.Geshi';
	}

	public function admin_xhtml_elements() {
	}

	public function admin_form_elements() {
	}

	public function admin_icons() {
		$x = get_defined_constants(true);
		$icons = $x['user'];
		foreach ($icons as $y => $z) {
			if (substr($y, 0, 5) !== 'ICON_') {
				unset($icons[$y]);
			}
		}

		App::uses('Folder', 'Utility');
		$folder = new Folder(IMAGES . 'icons');
		$content = $folder->read(true, true);
		$files = $content[1];

		$this->set(compact('icons', 'files'));
	}

	public function admin_flash_messages() {
		 CommonComponent::transientFlashMessage('Success-Message', 'success');
		 CommonComponent::transientFlashMessage('Warning-Message', 'warning');
		 CommonComponent::transientFlashMessage('Error-Message - a longer one to see if everything is working properly - lsdfhskdf sdfhsfhso fihso difhsdofih sdofhso fhsfioh sofihsodfsodifh sfhs iodfshdfihsdio fsdhof isdhf sfhisf', 'error');
		 CommonComponent::transientFlashMessage('Info-Message', 'info');
		 CommonComponent::transientFlashMessage('Default FlashMessage');
	}

}
