<?php

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaLoader;
use Cake\Utility\Security;
use Cake\View\View;
use Setup\SetupPlugin;
use Shim\Filesystem\Folder;
use TestApp\Application;
use TestApp\Controller\AppController;
use TestApp\Model\Entity\User;
use TestApp\Model\Table\UsersTable;

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

require ROOT . DS . 'config' . DS . 'bootstrap.php';

if (!defined('TB')) {
	define('TB', "\t");
}
if (!defined('NL')) {
	define('NL', PHP_EOL);
}

ini_set('intl.default_locale', 'de-DE');

require ROOT . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';
require CAKE . 'functions.php';

Configure::write('App', [
	'namespace' => 'TestApp',
	'encoding' => 'UTF-8',
	'paths' => [
		'templates' => [ROOT . DS . 'tests' . DS . 'test_app' . DS . 'templates' . DS],
	],
]);
Configure::write('debug', true);

Security::setSalt('123');

class_alias(AppController::class, 'App\Controller\AppController');
class_alias(View::class, 'App\View\AppView');

class_alias(UsersTable::class, 'App\Model\Table\UsersTable');
class_alias(User::class, 'App\Model\Entity\User');

class_alias(Application::class, 'App\Application');

mb_internal_encoding('UTF-8');

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

Cache::setConfig($cache);

Plugin::getCollection()->add(new SetupPlugin());

Security::setSalt('YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');

// Ensure default test connection is defined
if (!getenv('DB_URL')) {
	putenv('DB_URL=sqlite:///:memory:');
}

ConnectionManager::setConfig('test', [
	'url' => getenv('DB_URL') ?: null,
	'timezone' => 'UTC',
	'quoteIdentifiers' => true,
	'cacheMetadata' => true,
]);

if (getenv('FIXTURE_SCHEMA_METADATA')) {
	$loader = new SchemaLoader();
	$loader->loadInternalFile(getenv('FIXTURE_SCHEMA_METADATA'));
}
