<?php

namespace Setup\Controller\Admin;

use App\Controller\AppController;
use Cake\Event\EventInterface;

class UptimeController extends AppController {

	/**
	 * @var string|null
	 */
	protected ?string $defaultTable = '';

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);

		if ($this->components()->has('Auth') && method_exists($this->components()->get('Auth'), 'allow')) {
			$this->components()->get('Auth')->allow();
		} elseif ($this->components()->has('Authentication') && method_exists($this->components()->get('Authentication'), 'addUnauthenticatedActions')) {
			$this->components()->get('Authentication')->addUnauthenticatedActions(['index']);
		}
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function index() {
		$response = '';
		if (!isset($_REQUEST['key'])) {
			$response = 'OK';
		} else {
			if (preg_match('/^[a-f0-9]{32}$/', $_REQUEST['key'])) {
				$response = $_REQUEST['key'];
			}
		}

		return $this->response->withStringBody($response);
	}

}
