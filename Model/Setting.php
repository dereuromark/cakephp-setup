<?php
App::uses('SetupAppModel', 'Setup.Model');

/**
 * //TODO: allow Module.key syntax???
 */
class Setting extends SetupAppModel {

	public $displayField = 'value';

	public $order = array('Setting.modified' => 'DESC');

	public $Controller = null;

	public $sessionName = 'Settings';

	public $saveInSession = false;

	public $validate = array(
		'user_id' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField'
			),
		),
		'key' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField'
			),
		),
		'value' => array(
			'maxLength' => array(
				'rule' => array('maxLength', 255),
				'message' => array('valErrMaxCharacters %s', 255),
				'allowEmpty' => true
			),
		),
	);

	public $belongsTo = array(
		'User' => array(
			'className' => CLASS_USER,
			'foreignKey' => 'user_id',
			'conditions' => '',
			'fields' => array('id', 'username'),
			'order' => ''
		)
	);

	/**
	 * Needs to be init manually!
	 *
	 * @param Object ControllerInstance
	 * @return bool Success or NULL if nothing to do (already inited)
	 */
	public function init(Controller $Controller) {
		$this->Controller = $Controller;
		if (!isset($this->Session)) {
			if (!isset($this->Controller) || !isset($this->Controller->Session)) {
				return false;
			}
			$this->Session = $this->Controller->Session;
		}
		if ($this->saveInSession) {
			return $this->_init();
		}
		return null;
	}

	public function updateSession($data) {
		if (!isset($this->Session)) {
			return false;
		}
		if (!$this->saveInSession) {
			return null;
		}
		extract($data);
		return $this->Session->write($this->sessionName . '.' . $key, $value);
	}

	/**
	 * Already checked for session component!
	 */
	public function _init() {
		if ($this->Session->check($this->sessionName)) {
			return null;
		}
		$res = $this->read();
		if (!$res) {
			$res = array();
		}
		return $this->Session->write($this->sessionName, $res);
	}

	/**
	 * Like write, but for all config values at once + validation
	 */
	public function store($data, $validate = null) {
		$this->set($data);
		if ($validate) {
			$this->validate = $validate;
			if (!$this->validates()) {
				return false;
			}
		}
		foreach ($this->data[$this->alias] as $key => $value) {
			$this->write($key, $value);
		}
		return true;
	}

	/**
	 * Write a single config value
	 *
	 * @param key
	 * @param value (optional)
	 * NULL => delete
	 */
	public function write($key, $value = null) {
		if (empty($key)) {
			return false;
		}
		$conditions = array(
			'key' => $key
		);

		$res = $this->find('first', array('conditions' => $conditions));
		if (!empty($res)) {
			// update
			if ($value === null) {
				return (bool)$this->delete($res[$this->alias]['id']);
			}
			$this->id = $res[$this->alias]['id'];
			if ($this->saveField('value', $value)) {
				$this->updateSession(array('key' => $key, 'value' => $value));
				return true;
			}
			return false;
		}
		// new entry
		if ($value === null) {
			return true;
		}

		$this->create();
		$data = array(
			'key' => $key,
			'value' => $value,
		);
		if ($this->save($data)) {
			$this->updateSession($data);
			return true;
		}
		return false;
	}

	/**
	 * @return string value (or mixed $default on failure)
	 */
	public function read($key = null, $default = false) {
		$conditions = array(
		);
		$type = 'list';
		if ($key !== null) {
			$conditions['key'] = $key;
			$type = 'first';
		}

		$res = $this->find($type, array('fields' => array('key', 'value'), 'conditions' => $conditions));
		if (empty($res)) {
			return $default;
		}
		if ($type === 'first') {
			return $res[$this->alias]['value'];
		}
		return $res;
	}

	/**
	 * Like read, but with the option of merging defaults into it
	 *
	 * @param uid
	 * @param key (optional)
	 * @param defaults: simple array
	 */
	public function retrieve($key = null, $defaults = array()) {
		$res = $this->read($key, array());
		$res = array_merge((array)$defaults, $res);
		return $res;
	}

	/**
	 * Reset by key
	 */
	public function reset($key) {
		if (empty($key)) {
			return false;
		}
		$conditions = array();
		if (!empty($key)) {
			$conditions['key'] = $key;
		}
		return (bool)$this->deleteAll($conditions);
	}

}
