<?php
App::uses('SetupAppModel', 'Setup.Model');
class Configuration extends SetupAppModel {

	public $useTable = false;

	public $validate = array(
		'admin_name' => array(
			'minLength' => array(
				'rule' => array('minLength', 1),
				'message' => 'Please insert the admin name'
			)
		),
		'admin_email' => array(
			'email' => array(
				'rule' => array('email'),
				'message' => 'This is no valid email address'
			),
			'minLength' => array(
				'rule' => array('minLength', 6),
				'message' => 'Please insert admin email address'
			)
		),
		'admin_emailname' => array(
			'minLength' => array(
				'rule' => array('minLength', 1),
				'message' => 'Please insert the admin email name'
			)
		),
		'page_name' => array(
			'minLength' => array(
				'rule' => array('minLength', 1),
				'message' => 'Please insert the page name'
			)
		),
		'max_loginfail' => array(
			'comparison' => array(
				'rule' => array('comparison', '>', 1),
				'message' => 'Between 2 und 99'
			)
		),
		'max_emails' => array(
			'comparison' => array(
				'rule' => array('comparison', '>', 1),
				'message' => 'Between 2 und 99'
			)
		),
		'pw_minlength' => array(
			'comparison' => array(
				'rule' => array('comparison', '>', 1),
				'message' => 'Between 2 und 19'
			),
			'comparison2' => array(
				'rule' => array('comparison', '<', 19),
				'message' => 'Between 2 und 19'
			)
		),
		'timeout' => array(
			'comparison' => array(
				'rule' => array('comparison', '>', 1),
				'message' => 'Between 2 und 999'
			)
		)
	);

	//TODO: use cache!?

	public function load() {
		if (!$this->useTable) {
			return false;
		}
		$settings = $this->find('all', array('limit' => 1));

		foreach ($settings['Configuration'] as $variable => $value) {

			## NEW
			Configure::write(
				'Config.' . $variable,
				$value
				);

			## OLD:
			//define('CONFIG_'.strtoupper($variable), ''.$value);

		}
	}

	public function getActive() {
		if (!$this->useTable) {
			return false;
		}
		$active = $this->find('first', array('conditions' => array('active' => 1)));

		return $active;
	}

	public function setActive() {
		if (!$this->useTable) {
			return false;
		}
		//$active=$this->find('first', array('conditions'=>array('active'=>1)));

		return true;
	}

}

/*
KONFIGURATION - USAGE:

<p>You can use the value easily in your controller, view, or wherever you want:</p>

<pre name="code_snippet" class="php:nogutter:nocontrols">
$dog = Configure::read('Neutrino.theDog');
$man = Configure::read('Neutrino.theMan');
</pre>

<p>As you probably know, you can use Configure to get <em>all</em> the values by typing:</p>

<pre name="code_snippet" class="php:nogutter:nocontrols">
$settings = Configure::read('Neutrino');
</pre>

<p>Which ought to return an array of values:</p>

<pre><code>Array
(
	[theDog] =&gt; Brian Griffin
	[theMan] =&gt; Vic Mackey
)
</code></pre>

<p>As you can see, this system eliminates the need for settings cache because there is only one query to the database. Your settings are available everywhere, and modifying and adding new variables is easy to implement. If you need per-user settings, you can add additional fields in the database (such as the obvious user_id), and extend the idea even further.</p>

*/
