<?php

use Shim\Filesystem\Folder;

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('WINDOWS')) {
	if (DS == '\\' || substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

define('ROOT', dirname(__DIR__));
define('TMP', ROOT . DS . 'tmp' . DS);
define('LOGS', ROOT . DS . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('APP', sys_get_temp_dir());
define('APP_DIR', 'src');
define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . APP_DIR . DS);

define('WWW_ROOT', ROOT . DS . 'webroot' . DS);
define('TESTS', ROOT . DS . 'tests' . DS);

define('CONFIG', TESTS . 'config' . DS);

if (!defined('TB')) {
	define('TB', "\t");
}
if (!defined('NL')) {
	define('NL', PHP_EOL);
}

ini_set('intl.default_locale', 'de-DE');

require ROOT . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';

Cake\Core\Configure::write('App', [
	'namespace' => 'TestApp',
	'encoding' => 'UTF-8',
	'paths' => [
		'templates' => [ROOT . DS . 'tests' . DS . 'test_app' . DS . 'templates' . DS],
	],
]);
Cake\Core\Configure::write('debug', true);

Cake\Utility\Security::setSalt('123');

class_alias(TestApp\Controller\AppController::class, 'App\Controller\AppController');
class_alias(Cake\View\View::class, 'App\View\AppView');

class_alias(TestApp\Model\Table\UsersTable::class, 'App\Model\Table\UsersTable');
class_alias(TestApp\Model\Entity\User::class, 'App\Model\Entity\User');

mb_internal_encoding('UTF-8');

class_alias(TestApp\Application::class, 'App\Application');
if (!class_exists('App\Controller\AppController')) {
	class_alias(TestApp\Controller\AppController::class, 'App\Controller\AppController');
}
if (!class_exists('App\View\AppView')) {
	class_alias(Cake\View\View::class, 'App\View\AppView');
}

$Tmp = new Folder(TMP);
$Tmp->create(TMP . 'cache/models', 0770);
$Tmp->create(TMP . 'cache/persistent', 0770);
$Tmp->create(TMP . 'cache/views', 0770);

$cache = [
	'default' => [
		'engine' => 'File',
		'path' => CACHE,
	],
	'_cake_core_' => [
		'className' => 'File',
		'prefix' => 'crud_myapp_cake_core_',
		'path' => CACHE . 'persistent/',
		'serialize' => true,
		'duration' => '+10 seconds',
	],
	'_cake_model_' => [
		'className' => 'File',
		'prefix' => 'crud_my_app_cake_model_',
		'path' => CACHE . 'models/',
		'serialize' => 'File',
		'duration' => '+10 seconds',
	],
];

Cake\Cache\Cache::setConfig($cache);

Cake\Core\Plugin::getCollection()->add(new Setup\Plugin());

Cake\Utility\Security::setSalt('YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');

if (getenv('db_dsn')) {
	Cake\Datasource\ConnectionManager::setConfig('test', [
		'className' => 'Cake\Database\Connection',
		'url' => getenv('db_dsn'),
		'timezone' => 'UTC',
		'quoteIdentifiers' => true,
		'cacheMetadata' => true,
	]);

	return;
}

// Ensure default test connection is defined
if (!getenv('db_class')) {
	putenv('db_class=Cake\Database\Driver\Sqlite');
	putenv('db_dsn=sqlite:///:memory:');
}

Cake\Datasource\ConnectionManager::setConfig('test', [
	'className' => 'Cake\Database\Connection',
	'url' => getenv('db_dsn') ?: null,
	'driver' => getenv('db_class') ?: null,
	'timezone' => 'UTC',
	'quoteIdentifiers' => true,
	'cacheMetadata' => true,
]);

if (env('FIXTURE_SCHEMA_METADATA')) {
	$loader = new Cake\TestSuite\Fixture\SchemaLoader();
	$loader->loadInternalFile(env('FIXTURE_SCHEMA_METADATA'));
}
