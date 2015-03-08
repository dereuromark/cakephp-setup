<?php

App::uses('Component', 'Controller');
App::uses('FlashComponent', 'Tools.Controller/Component');
App::uses('Configure', 'Core');

/**
 *
 * @author Mark Scherer
 * @license MIT
 */
class MaintenanceComponent extends Component {

	//public $components = array('Tools.Flash');

	protected $_defaultConfig = [
		'check' => true,
	];

	/**
	 * Main interaction.
	 *
	 * @param Controller $controller
	 * @return void
	 */
	public function initialize(Controller $Controller) {
		// maintenance mode?
		if ($overwrite = Configure::read('Maintenance.overwrite')) {
			// if this is reachable, the whitelisting is enabled and active
			$message = __d('setup', 'Maintenance mode active - your IP %s is in the whitelist.', $overwrite);
			FlashComponent::transientMessage($message, 'warning');
		}
	}

}
