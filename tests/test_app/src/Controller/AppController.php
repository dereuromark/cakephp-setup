<?php

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;

class AppController extends Controller {

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
	 * @return mixed
	 */
	public function beforeFilter(EventInterface $event) {
		parent::beforeFilter($event);
		if (Configure::read('Test.healthcheckRedirect')) {
			return $this->redirect('/login');
		}

		return null;
	}

}
