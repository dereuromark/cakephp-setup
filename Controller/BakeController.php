<?php
App::uses('SetupAppController', 'Setup.Controller');

/**
 */
class BakeController extends SetupAppController {

	public $uses = array('Setup.Bake');

	public function beforeFilter() {
		parent::beforeFilter();
	}

	public function admin_index() {
	}

	public function admin_views(Controller $controller = null) {
		$controllers = $this->_getControllers();

		if (!empty($controller) && array_key_exists($controller, $controllers)) {
			if ($this->_bakeViews($controllers[$controller])) {
				$this->Common->flashMessage(__('Views for controller %s baked', $controller), 'success');
				return $this->Common->postRedirect(array('action' => 'views'));
			}
			die('Error');
			$this->Common->flashMessage(__('Views for controller %s could not get baked', $controller), 'error');
		}

		$this->set(compact('controllers'));
	}

	public function admin_controllers(Model $model = null) {
		$models = $this->_getModels();

		if (!empty($model) && array_key_exists($model, $models)) {
			if ($this->_bakeController($models[$model])) {
				$this->Common->flashMessage(__('Controller for model %s baked', $model), 'success');
				return $this->Common->postRedirect(array('action' => 'controllers'));
			}
			die('E');
			$this->Common->flashMessage(__('Controller for model %s could not get baked', $model), 'error');
		}

		$this->set(compact('models'));
	}

	public function admin_models($table = null) {
		$this->Bake = ClassRegistry::init('Setup.Bake');
		$datasource = $this->Bake->getDataSource();
		if (!$datasource->enabled()) {
			throw new InternalErrorException('Database setup invalid');
		}
		$prefix = $this->Bake->tablePrefix;
		$stats = array();
		$tables = $datasource->listSources();
		$stats['all'] = count($tables);
		if ($prefix) {
			foreach ($tables as $key => $val) {
				if (!startsWith($val, $prefix)) {
					unset($tables[$key]);
				} else {
					$tables[$key] = substr($val, strlen($prefix));
				}
			}
		}
		$stats['app'] = count($tables);

		// check if already a model available
		foreach ($tables as $key => $val) {

		}

		if (!empty($table) && in_array($table, $tables)) {
			if ($this->_bakeModel($table)) {
				$modelName = Inflector::camelize(Inflector::singularize($table));
				$this->Common->flashMessage(__('Model %s baked', $modelName), 'success');
				return $this->Common->postRedirect(array('action' => 'models'));
			}
			$this->Common->flashMessage(__('Model %s could not get baked', $modelName), 'error');
		}

		$this->set(compact('tables', 'stats'));
	}

	/**
	 * @return array - only suitable controllers
	 */
	protected function _getControllers() {
		$controllers = $this->_getObjects('Controller');
		return $controllers;
	}

	/**
	 * @return array - only suitable models
	 */
	protected function _getModels() {
		return $this->_getObjects('Model');
		/*
		$models = array();

		$plugins = CakePlugin::loaded();
		foreach ($plugins as $plugin) {
			$pluginModels = App::objects($plugin.'.Model');
			foreach ($pluginModels as $pluginModel) {
				$models[$pluginModel] = $plugin.'.'.$pluginModel;
			}
		}
		$models = array_reverse($models);

		$appModels = App::objects('Model');
		$appModels = array_reverse($appModels);
		foreach ($appModels as $appModel) {
			$models[$appModel] = $appModel;
		}

		foreach ($models as $key => $val) {
			if (strpos($val, 'AppModel') !== false) {
				unset($models[$key]);
			}
		}
		$models = array_reverse($models);
		return $models;
		*/
	}

	protected function _getObjects($type, $plugin = true) {
		$objects = array();

		if ($plugin) {
			$plugins = CakePlugin::loaded();
			foreach ($plugins as $plugin) {
				$pluginObjects = App::objects($plugin . '.' . $type);
				foreach ($pluginObjects as $pluginObject) {
					$objects[$pluginObject] = $plugin . '.' . $pluginObject;
				}
			}
			$objects = array_reverse($objects);
		}
		$appObjects = App::objects($type);
		$appObjects = array_reverse($appObjects);
		foreach ($appObjects as $appObject) {
			$objects[$appObject] = $appObject;
		}

		foreach ($objects as $key => $val) {
			if (strpos($val, 'App' . $type) !== false) {
				unset($objects[$key]);
			}
		}
		$objects = array_reverse($objects);
		if ($type !== 'Model') {
			foreach ($objects as $key => $val) {
				unset($objects[$key]);
				$key = substr($key, 0, strlen($key) - strlen($type));
				$objects[$key] = $val;
			}
		}
		return $objects;
	}

	protected function _bakeViews(Controller $controller) {
		list($plugin, $controllerName) = pluginSplit($controller);
		App::uses('ViewTask', 'Console/Command/Task');
		$BakeTask = new ViewTask();
		foreach ($BakeTask->tasks as $task) {
			$taskClass = $task . 'Task';
			App::uses($taskClass, 'Console/Command/Task');
			$BakeTask->{$task} = new $taskClass();
		}
		if ($plugin) {
			$plugin .= '.';
		}
		App::uses($controllerName, $plugin . 'Controller');
		$Controller = new $controllerName;

		$BakeTask->interactive = false;
		$BakeTask->args = array('CaseFile');
		$BakeTask->params = array('theme' => 'setup');
		$BakeTask->Template->initialize();
		$BakeTask->initialize();

		$BakeTask->Template->params = array('theme' => 'setup');
		$BakeTask->execute();

		// admin too
		$BakeTask->Template->params = array('admin' => true, 'theme' => 'setup');
		$BakeTask->execute();

		return true;
	}

	protected function _bakeController(Model $model) {
		list($plugin, $modelName) = pluginSplit($model);
		App::uses('ControllerTask', 'Console/Command/Task');
		$BakeTask = new ControllerTask();
		foreach ($BakeTask->tasks as $task) {
			$taskClass = $task . 'Task';
			App::uses($taskClass, 'Console/Command/Task');
			$BakeTask->{$task} = new $taskClass();
		}
		$BakeTask->interactive = false;
		$BakeTask->args = array($modelName);
		$BakeTask->params = array('admin' => true, 'public' => true);
		$BakeTask->Template->params = array('theme' => 'setup');
		$BakeTask->Test->Template = $BakeTask->Template;
		$BakeTask->initialize();
		$BakeTask->execute();
		return true;
	}

	protected function _bakeModel($table) {
		App::uses('ModelTask', 'Console/Command/Task');
		$ModelTask = new ModelTask();
		foreach ($ModelTask->tasks as $task) {
			$taskClass = $task . 'Task';
			App::uses($taskClass, 'Console/Command/Task');
			$ModelTask->{$task} = new $taskClass();
			$ModelTask->{$task}->interactive = false;
		}
		$ModelTask->interactive = false;
		$ModelTask->args = array($table);
		$ModelTask->Template->params = array('theme' => 'setup');
		$ModelTask->Fixture->Template = $ModelTask->Template;
		$ModelTask->Test->Template = $ModelTask->Template;
		$ModelTask->initialize();
		$ModelTask->execute();
		return true;
	}

	public function admin_tables() {
		if ($this->Common->isPosted()) {
			$tables = $this->_parseInput($this->request->data['Bake']['import'], 'string', $this->request->data['Bake']['auto_sort']);

			// bug? why do we need to do that?
			$this->Bake = ClassRegistry::init('Setup.Bake');

			$sql = $this->_buildSql($tables);
			$validates = true; //TODO

			if (!$this->request->data['Bake']['cleanup_and_validation_only'] && $validates) {
				try {
					if ($this->request->data['Bake']['delete_existing_tables']) {
						foreach ($tables as $table => $fields) {
							$table = $this->Bake->tablePrefix . $table;
							$this->Bake->query('DROP TABLE IF EXISTS `' . $table . '`');
						}
					}

					$res = $this->Bake->query($sql);
				} catch (Exception $e) {
					$this->Common->flashMessage($e->getMessage(), 'error');
					$error = true;
				}
				if (empty($error) && !empty($res)) {
					$this->Common->flashMessage(__('Executed'), 'success');
					//$this->redirect(array());
				}
			}

			$this->request->data['Bake']['import'] = $this->_buildOutput($tables);
			$this->set(compact('sql'));

		} else {
			$this->request->data['Bake']['import'] = 'my_examples {
	field: enum,
	other_field: int,
	id: uuid,

}
my_other_examples {
	field: text,
	other_field: decimal,
	id: aiid,

}';
			$this->request->data['Bake']['cleanup_and_validation_only'] = true;
			$this->request->data['Bake']['auto_sort'] = true;
		}

		$types = $this->types; // array_combine(array_keys($this->types), array_keys($this->types));
		$this->set(compact('types'));
	}

	/**
	 * @return string
	 */
	public function _buildSql($tables) {
		$statement = 'CREATE TABLE `:table` (' . PHP_EOL . ':fields,' . PHP_EOL . TB . 'PRIMARY KEY (`id`)' . PHP_EOL . ') COLLATE=utf8_unicode_ci;'; //IF NOT EXISTS

		$tableStrings = array();
		foreach ($tables as $table => $fields) {
			$fieldStrings = array();
			foreach ($fields as $field => $type) {
				$sql = $this->types[$type];
				$length = null;
				if (isset($this->defaultLengths[$type])) {
					$length = $this->defaultLengths[$type];
					$sql = String::insert($sql, array('length' => $length));
				}
				$fieldStrings[] = TB . '`' . $field . '` ' . $sql;
			}
			$fieldStrings = implode(',' . PHP_EOL, $fieldStrings);
			$table = $this->Bake->tablePrefix . $table;
			$tableStrings[] = String::insert($statement, array('table' => $table, 'fields' => $fieldStrings));
		}

		return implode(PHP_EOL, $tableStrings);
	}

	/**
	 * @return string
	 */
	protected function _buildOutput($tables) {
		$tableStrings = array();
		foreach ($tables as $table => $fields) {
			$string = $table . ' {' . PHP_EOL;
			foreach ($fields as $field => $type) {
				$string .= TB . $field . ': ' . $type . ',' . PHP_EOL;
			}
			$string .= '}' . PHP_EOL;
			$tableStrings[] = $string;
		}

		return implode(PHP_EOL, $tableStrings);
	}

	/**
	 * @return array
	 */
	protected function _parseInput($string, $default = 'string', $autoSort = false, $autoTimestamps = true) {
		$tables = array();

		$pieces = explode('}', $string);
		foreach ($pieces as $piece) {
			$piece = trim($piece);
			if (($pos = strpos($piece, '{')) === false) {
				continue;
			}
			$table = trim(substr($piece, 0, $pos));
			$fieldPieces = explode(',', substr($piece, $pos + 1));
			foreach ($fieldPieces as $piece) {
				$elements = explode(':', $piece, 2);
				$field = trim(strtolower(array_shift($elements)));
				if (empty($field)) {
					continue;
				}
				$type = null;
				if (!empty($elements) && ($type = trim(array_shift($elements)))) {
					$type = strtolower($type);
				}
				$type = $this->type($type, $field, $default);
				$tables[$table][$field] = $type;
			}
		}

		// sort
		foreach ($tables as $table => $fields) {
			$primaryKeys = array();
			$foreignKeys = array();
			$fieldArray = array();
			foreach ($fields as $field => $type) {
				if ($field === 'id') {
					$primaryKeys[$field] = $type;
				} elseif (substr($field, -3, 3) === '_id') {
					$foreignKeys[$field] = $type;
				} else {
					$fieldArray[$field] = $type;
				}
			}
			if ($autoTimestamps && !isset($fieldArray['created'])) {
				$fieldArray['created'] = 'datetime';
			}
			if ($autoTimestamps && !isset($fieldArray['modified'])) {
				$fieldArray['modified'] = 'datetime';
			}
			if (empty($primaryKeys)) {
				$primaryKeys['id'] = 'aiid';
			}
			if ($autoSort) {
				$fieldArray = array_merge($primaryKeys, $fieldArray, $foreignKeys);
			} else {
				$fieldArray = array_merge($primaryKeys, $fields);
			}
			$tables[$table] = $fieldArray;
		}
		return $tables;
	}

	/**
	 * @return string
	 */
	public function type($type, $field, $default = 'string') {
		if ($type === 'int') {
			$type = 'integer';
		} elseif ($type === 'bool') {
			$type = 'boolean';
		} elseif ($type === 'textarea') {
			$type = 'text';
		} elseif ($type === 'varchar') {
			$type = 'string';
		}

		if (isset($this->types[$type])) {
			return $type;
		}

		// try to use the field for information
		if (endsWith($field, '_id')) {
			$type = 'integer';
		} elseif (in_array($field, $this->matchings['date']) || endsWith($field, '_date')) {
			$type = 'date';
		} elseif (in_array($field, $this->matchings['time']) || endsWith($field, '_time')) {
			$type = 'time';
		} elseif (in_array($field, $this->matchings['datetime']) || endsWith($field, '_datetime') || startsWith($field, 'modified_')) {
			$type = 'datetime';
		} elseif (in_array($field, $this->matchings['boolean'])) {
			$type = 'boolean';
		} elseif (in_array($field, $this->matchings['enum']) || endsWith($field, '_status') || endsWith($field, '_option') || endsWith($field, '_type')) {
			$type = 'enum';
		} elseif (in_array($field, array('description', 'note', 'comment'))) {
			$type = 'text';
		} elseif (in_array($field, array('weight', 'height', 'volume', 'value'))) {
			$type = 'float';
		} elseif (in_array($field, array('amount', 'price', 'costs'))) {
			$type = 'decimal';
		} elseif (in_array($field, array('duration', 'quantity', 'count'))) {
			$type = 'integer';
		}

		if (isset($this->types[$type])) {
			return $type;
		}
		return $default;
	}

	protected $types = array(
		'boolean' => 'TINYINT(1) UNSIGNED NOT NULL default \'0\'',
		'enum' => 'TINYINT(2) UNSIGNED NOT NULL default \'0\'',
		'integer' => 'INT(10) UNSIGNED NOT NULL default \'0\'',
		'decimal' => 'DECIMAL(9,2) NOT NULL default \'0.0\'',
		'float' => 'FLOAT(9,2) NOT NULL default \'0.0\'',
		'datetime' => 'DATETIME NOT NULL',
		'date' => 'DATE NOT NULL',
		'time' => 'DATETIME NOT NULL',
		'string' => 'VARCHAR(:length) COLLATE utf8_unicode_ci NOT NULL',
		'text' => 'TEXT NOT NULL',
		'aiid' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
		'uuid' => 'CHAR(36) COLLATE utf8_unicode_ci NOT NULL',
		'char' => 'CHAR(:length) COLLATE utf8_unicode_ci NOT NULL'
	);

	protected $matchings = array(
		'date' => array('date', 'day', 'date_of_birth'),
		'time' => array('time'),
		'datetime' => array('datetime', 'created', 'modified'),
		'boolean' => array('active', 'approved', 'published', 'notified', 'sent', 'deleted'),
		'enum' => array('enum', 'status', 'state', 'gender', 'flag', 'type', 'priority', 'visibility', 'option', 'category', 'level', 'condition'),
	);

	protected $defaultLengths = array(
		'string' => 255,
		'char' => 255,
		'float' => '9,2',
		'decimal' => '9,2',
		'enum' => 2,
	);

}
