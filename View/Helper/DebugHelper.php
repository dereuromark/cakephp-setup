<?php
App::uses('AppHelper', 'View/Helper');
App::uses('DebugLib', 'Setup.Lib');
App::uses('CakeNumber', 'Utility');
App::uses('File', 'Utility');

if (!defined('BR')) {
	define('BR', '<br />');
}

/**
 * A helper to display a debug bar at the bottom of each page to quickly tab through all debug output.
 *
 * Only used in debug mode! needs to be started manually.
 *
 * Remember functionality can be modified using Debug.rememberEngine (ajax, cookie, ...)
 * in Configure.
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.0
 */
class DebugHelper extends AppHelper {

	public $helpers = array('Html', 'Session', 'Tools.Datetime');

	public $level = 0;

	public $debugContent = array('1' => array(), '2' => array(), '3' => array());

	public $model = null;

	protected $_rememberEngine = 'cookie';

	public function __construct(View $View, $level = null, $options = array()) {
		parent::__construct($View, $options);

		$this->_ViewProperties = $this->_objectToArray($View);

		if (!empty($options['model'])) {
			$this->_useModel($options['model']);
		}

		$this->level = (int)$level;
		$this->_debug($options);

		if (Configure::read('Debug.rememberEngine') !== null) {
			$this->_rememberEngine = Configure::read('Debug.rememberEngine');
		}
	}

	protected function _objectToArray($obj, $level = 5) {
		$_arr = is_object($obj) ? get_object_vars($obj) : $obj;
		$arr = array();
		if ($level < 1) {
			return $obj;
		}
		foreach ($_arr as $key => $val) {
			$val = (is_array($val) || is_object($val)) ? self::_objectToArray($val, $level--) : $val;
			$arr[$key] = $val;
		}
		return $arr;
	}

	protected function _useModel($name) {
		$this->model = $name;
	}

	/**
	 * Shows pr() messages, even with debug=0
	 *
	 * @access public (in the view via $html helper etc.)
	 */
	public function pre($array, $class = null, $escape = true) {
		$preArray = '';
		$preClass = '';

		if (!empty($class)) {
			$preClass = $class;
		}
		$res = Debugger::exportVar($array, 10);
		if ($escape) {
			$res = h($res);
		}
		return '<pre ' . $preClass . '>' . nl2br($res) . '</pre>';
	}

	/**
	 * Change default "active/visible" tab
	 */
	public function setDefault() {
	}

	/**
	 * Final print function
	 */
	public function show() {
		$output = '<div class="tabs cake-sql-log debug_request">';
		$header = '<ul class="tabNavigation">';
		$body = '';

		for ($i = 1; $i <= $this->level; $i++) {
			foreach ($this->debugContent[$i] as $title => $content) {
				if (!empty($content)) {
					$sluggedTitle = strtolower(Inflector::slug($title));
					$header .= '<li><a href="#debug-' . $sluggedTitle . '" id="tab-' . $sluggedTitle . '">' . $title . '</a></li>';
					$body .= '<div class="content" id="debug-' . $sluggedTitle . '">' . $content . '</div>';
				}
			}
		}
		$header .= '</ul>';

		$output .= $header . $body . '</div>';
		$output .= $this->Html->css('/setup/css/tabs');
		$output .= $this->Html->script('/setup/js/tabs');

		if ($this->_rememberEngine) {

			if ($this->_rememberEngine === 'ajax') {
				$debugTab = $this->Session->read('Debug.tab');

			} else {
				$output .= $this->Html->script('/setup/js/jquery.cookie');

				/*
				App::import('Component', 'Cookie');
				$this->Cookie = new CookieComponent();
				$this->Cookie->initialize(new Controller(), array());
				$this->Cookie->startup();
				$debugTab = $this->Cookie->read('Debug.tab');
				*/
				if (isset($_COOKIE) && isset($_COOKIE['DebugTab'])) {
					$debugTab = $_COOKIE['DebugTab'];
				}
			}

			if (!empty($debugTab)) {
				$rememberedId = $debugTab;
			} else {
				$rememberedId = '';
			}

			$url = $this->Html->url(array('plugin' => 'setup', 'controller' => 'debug', 'action' => 'tab'));
			if (!(substr($url, -1, 1) === '/')) {
				$url .= '/';
			}

			if ($this->_rememberEngine === 'ajax') {
				$script = '
	$(\'.tabNavigation li a\').click(function () {
		var selvalue = $(this).attr(\'id\');
		var targeturl = "' . $url . 'tab:" + selvalue + "/";
		if (remember != \'\' && selvalue == remember) {
			return false;
		}
		remember = selvalue;
		$.ajax({
			type: \'post\', url: targeturl,
		beforeSend: function(xhr) {
			xhr.setRequestHeader(\'Content-type\', \'application/x-www-form-urlencoded\');
		},
			success: function(html) {
				if (html !=\'\') {
					alert(\'Problem with the debug remember request\');
				}
			}
		});
	});';

			} else {
				$script = '
	$(\'.tabNavigation li a\').click(function () {
		var selvalue = $(this).attr(\'id\');
		var expire = 365; // days
		if (remember != \'\' && selvalue == remember) {
			return false;
		}
		//$.cookie(\'DebugTab\', null);
		$.cookie(\'DebugTab\', selvalue, { expires: expire });
	});';

			}

			$output .= $this->Html->scriptBlock('
jQuery(document).ready(function() {
	var remember = \'' . $rememberedId . '\';

	' . $script . '

	' . (!empty($rememberedId) ? '

	jQuery(\'div.tabs ul.tabNavigation a#' . $rememberedId . '\').click();

	' : '') . '

});
');
		}

		$output .= $this->Html->scriptBlock('
jQuery(function() {
	jQuery("table.cake-sql-log tr").children("td:contains(\'DESCRIBE \')").parent().remove();
	jQuery("table.cake-sql-log tr").children("td:contains(\'SHOW FULL COLUMNS \')").parent().remove();
});
');

		return $output;
	}

	/**
	 * Adds a new tab
	 * takes strings AND arrays now (automatic switch)
	 */
	public function add($level, $title = null, $content = null) {
		$level = (int)$level;
		if ($level < 1 || $level > 3 || empty($title) || empty($content)) {
			return false;
		}

		if (is_array($content)) {
			$content = $this->pre($content);
		}
		$this->debugContent[$level][$title] = $content;
		return true;
	}

	/**
	 * Sets normal default tabs
	 */
	protected function _debug($options = null) {
		$res = '<div class="globals">';
		$res .= '<div style="float:right">' . $this->Html->link('Error-Logs', array('plugin' => 'setup', 'admin' => true, 'controller' => 'configuration', 'action' => 'logs')) . '</div>';
		$res .= '<div style="float:right; margin-right: 20px;">' . $this->Html->link('Configuration', array('plugin' => 'setup', 'admin' => true, 'controller' => 'configuration', 'action' => 'index')) . '</div>';
		//$res .=  xdebug_time_index();
		$res .= 'Cake-Version: ' . $this->versionCake() . BR . BR;
		$res .= __('rendered in %s', '<b>' . number_format(round(microtime(true) - $_SERVER['REQUEST_TIME'], 3), 3, ',', '') . ' s</b>') . BR;
		$res .= '</div>';
		$this->add(1, 'Start', $res);

		/** Session Post Get... **/
		$res = '<table width="100%"><tr><td>';
		$res .= '<div class="globals"><b>GET</b><br/><pre>' . h(print_r($_GET, true)) . '</pre>';
		$res .= '</div>';
		$res .= '<div class="globals"><b>POST</b><br/><pre>' . h(print_r($_POST, true)) . '</pre>';
		$res .= '</div>';
		$res .= '<div class="globals"><b>SESSION</b><br/><pre>' . h(print_r(!empty($_SESSION) ? $_SESSION : $this->Session->read(), true)) . '</pre>';
		$res .= '</div>';
		$res .= '</td><td>';
		$res .= '<div class="globals"><b>REQUEST</b><br/><pre>' . h(print_r($_REQUEST, true)) . '</pre>';
		$res .= '</div>';
		$res .= '<div class="globals"><b>COOKIE</b><br/><pre>' . h(print_r($_COOKIE, true)) . '</pre></div>';

		if (!empty($_FILES)) {
			$res .= '<div class="globals"><b>FILES</b><br/><pre>' . h(print_r($_FILES, true)) . '</pre></div>';
		}

		$res .= '</td></tr></table>';
		$this->add(1, '$_VARS', $res);

		/** this->data... **/
		//$res = '<table width="100%"><tr><td>';
		//<b>this-&gt;data</b><br/>
		$res = '<div class="globals"><pre>' . h(print_r($this->_View->data, true)) . '</pre>';
		$res .= '</div>';
		//$res.= '</td><td>';
		//$res.= '</td></tr></table>';

		$this->add(1, 'this-&gt;data', $res);

		/** Passed from controller to view **/
		$viewVars = $this->_View->viewVars;
		if (isset($viewVars['content_for_layout'])) {
			unset($viewVars['content_for_layout']);
		}
		if (isset($viewVars['scripts_for_layout'])) {
			unset($viewVars['scripts_for_layout']);
		}
		// play nice with DebugKit
		if (isset($viewVars['debugToolbarPanels'])) {
			unset($viewVars['debugToolbarPanels']);
		}
		if (isset($viewVars['debugToolbarJavascript'])) {
			unset($viewVars['debugToolbarJavascript']);
		}
		$viewVars = array_reverse($viewVars);

		$res = '<table width="100%"><tr><td>';
		$res .= '<div class="globals"><b>View Vars</b><br/>' . $this->pre($viewVars) . '';
		$res .= '</div>';
		$res .= '</td></tr></table>';
		$this->add(1, 'ViewVars', $res);

		/** View parameters **/
		$res = '<table width="100%" style="table-layout:fixed;"><tr><td style="padding-right:10px;">';

		$res .= '<h3>Params</h3>';
		$res .= '<b>View parameters</b><br /><br />';
		$res .= '$this->request->base: ' . (!empty($this->_View->base) ? $this->_View->base : '<i>n/a</i>') . '<br/>';
		$res .= '$this->request->here: ' . (!empty($this->_View->here) ? $this->_View->here : '<i>n/a</i>') . '<br/>';
		$res .= '$this->name: ' . (!empty($this->_View->name) ? $this->_View->name : '<i>n/a</i>') . '<br/>';
		$res .= '$this->viewPath: ' . (!empty($this->_View->viewPath) ? $this->_View->viewPath : '<i>n/a</i>') . '<br/>';
		$res .= '$this->themePath: ' . (!empty($this->_View->themePath) ? $this->_View->themePath : '<i>n/a</i>') . '<br/>';
		$res .= '$this->ext: ' . (!empty($this->_View->ext) ? $this->_View->ext : '<i>n/a</i>') . '<br/>';
		$res .= '$this->layout: ' . (!empty($this->_View->layout) ? $this->_View->layout : '<i>n/a</i>') . '<br/>';
		$res .= '$this->pageTitle: ' . (!empty($this->_View->pageTitle) ? $this->_View->pageTitle : '<i>n/a</i>') . '<br/>';
		$res .= '$this->validationErrors: ' . (!empty($this->_View->validationErrors) ? '<pre class="">' . h(print_r($this->_View->validationErrors, true)) . '</pre>' : '<i>n/a</i>') . '<br/>';
		$res .= '<br /><br />';

		/** URL **/
		$res .= '<b>Url Resolving</b><br /><br />';
		$res .= '$this->request->params[\'prefix\']: ' . (!empty($this->_View->request->params['prefix']) ? h($this->_View->request->params['prefix']) : '<i>n/a</i>') . '<br/>';
		$res .= '$this->request->params[\'admin\']: ' . (!empty($this->_View->request->params['admin']) ? h($this->_View->request->params['admin']) : '<i>n/a</i>') . '<br/>';
		$res .= '$this->request->params[\'plugin\']: ' . (!empty($this->_View->request->params['plugin']) ? h($this->_View->request->params['plugin']) : '<i>n/a</i>') . '<br/>';

		$res .= '$this->request->params[\'controller\']: ' . (!empty($this->_View->request->params['controller']) ? h($this->_View->request->params['controller']) : '<i>n/a</i>') . '<br/>';
		$res .= '$this->request->params[\'action\']: ' . (!empty($this->_View->request->params['action']) ? h($this->_View->request->params['action']) : '<i>n/a</i>') . '<br/>';

		if (!empty($this->_View->request->params['pass'])) {
			$res .= '$this->request->params[\'pass\']:' . pre(h($this->_View->request->params['pass']));
		}

		if (!empty($this->_View->request->params['named'])) {
			$res .= '$this->request->params[\'named\']:' . pre(h($this->_View->request->params['named']));
		}
		if (!empty($this->_View->request->params['ext'])) {
			$res .= '$this->request->params[\'ext\']:' . pre(h($this->_View->request->params['ext']));
		}
		if (isset($this->_View->request->query)) {
			$res .= '$this->request->query:' . pre(h($this->_View->request->query));
		}
		if (!empty($this->_View->request->params['isAjax'])) {
			$res .= '$this->request->params[\'isAjax\']:' . pre(h($this->_View->request->params['isAjax']));
		}
		if (!empty($this->_View->request->params['models'])) {
			$res .= '$this->request->params[\'models\']: ' . pre($this->_View->request->params['models']) . '<br/>';
		}

		$res .= '</td><td>';

		/** Dynamic Stuff **/
		$res .= '<h3>Live</h3>';
		$res .= '<p>';

		$res .= 'session_id(): ' . session_id() . '<br />';
		$res .= 'time(): ' . time() . ' (' . $this->Datetime->niceDate(time()) . ')' . BR;

		$res .= 'Session Expiration: ' . (!empty($_SESSION['Config']['time']) ? date(FORMAT_NICE_YMDHMS, $_SESSION['Config']['time']) : '---') . BR;
		$res .= '</p>';

		$res .= '<h3>Environment</h3>';
		$res .= '<p>';
		$res .= 'env(\'HTTP_HOST\'): ' . env('HTTP_HOST') . ' (official referer method)<br />';
		$res .= 'env(\'HTTP_REFERER\'): ' . env('HTTP_REFERER') . ' (not to trust!)<br />';

		$res .= 'env(\'CGI_MODE\'): ' . env('CGI_MODE') . '<br />';
		$res .= 'env(\'PHP_SELF\'): ' . env('PHP_SELF') . '<br />';
		$res .= 'env(\'HTTP_BASE\'): ' . env('HTTP_BASE') . '<br />';
		$res .= 'env(\'DOCUMENT_ROOT\'): ' . env('DOCUMENT_ROOT') . '<br />';

		$res .= 'env(\'SCRIPT_NAME\'): ' . env('SCRIPT_NAME') . '<br />';
		$res .= 'env(\'REDIRECT_QUERY_STRING\'): ' . env('REDIRECT_QUERY_STRING') . '<br />';
		$res .= 'env(\'REDIRECT_URL\'): ' . env('REDIRECT_URL') . '<br />';
		$res .= 'URL length: ' . (mb_strlen(env('REDIRECT_URL')) + mb_strlen(env('REDIRECT_QUERY_STRING'))) . ' (of 2,048)<br />';

		$res .= 'env(\'HTTPS\'): ' . env('HTTPS') . '<br />';

		$res .= 'env(\'REMOTE_ADDR\'): ' . ($ip = env('REMOTE_ADDR')) . '<br />';
		$res .= 'gethostbyaddr(env(\'REMOTE_ADDR\')): ' . ($ip ? gethostbyaddr($ip) : '' ) . '<br />';
		$res .= 'env(\'HTTP_USER_AGENT\'): ' . env('HTTP_USER_AGENT') . '<br />';

		$res .= '</p>';

		$res .= '<h3>System</h3>';

		//$res .= 'Lazy Loading: '.($this->_lazyLoading() ? 'JA' : 'NEIN').'<br />';
		$res .= 'Opcode Cache: ' . ($this->_opCodeCache() ? 'JA' : 'NEIN') . '<br />';
		$res .= 'XDebug: ' . ($this->_xdebug() ? 'JA' : 'NEIN') . '<br />';
		$res .= 'Memory Usage: ' . CakeNumber::toReadableSize(DebugLib::memoryUsage()) . ' (Peak: ' . CakeNumber::toReadableSize(DebugLib::peakMemoryUsage()) . ')';
		$res .= '<br />';
		$res .= 'PHP-Version: ' . $this->versionPHP() . '<br />';
		// currently: just read out "http://www.php.net/downloads.php -> <h2>...</h2>"

		$res .= 'DB-Version: ' . $this->versionDB() . '';
		$res .= '</p>';

		$res .= '<p>';
		if (class_exists('DATABASE_CONFIG')) {
			$configObject = new DATABASE_CONFIG();
			if (method_exists($configObject, 'current')) {
				$currentConfig = $configObject->current(true);
				if (is_array($currentConfig)) {
					$currentConfig = pre($currentConfig);
				}
				$res .= 'Database Config: ' . $currentConfig;
			}
		} else {
			$res .= 'No Database Config found.';
		}
		$res .= '</p>';
		$res .= '<p>';

		$res .= '<u>Core Config:</u><br />';
		$res .= 'Security Level: ' . Configure::read('Security.level') . '<br />';
		$settings = Cache::settings();
		$res .= 'Cache-Engine: ' . (!empty($settings['engine']) ? $settings['engine'] : '<b>NONE</b>') . '<br />';
		if (!empty($settings['engine']) && $settings['engine'] === 'File') {
			$res .= 'Cache-Folder writable: ' . (is_writable(TMP) ? 'YES' : '<b>NO</b>') . '<br />';
		}
		$res .= '</p>';

		$res .= '<u>Other:</u>';
		$res .= '<p>';
		// if salt and cipher key are altered in core.php
		Debugger::checkSecurityKeys();
		$res .= '</p>';

		$res .= '<h3>Loaded Files</h3>';
		$res .= '<p>';
		$files = get_included_files();
		foreach ($files as $key => $val) {
			$files[$key] = str_replace(ROOT, '', $val);
		}
		$res .= '<details><summary>' . count($files) . ' ' . __('Files') . '</summary>';
		$res .= pre($files);
		$res .= '</details>';
		$res .= '</p>';

		$res .= '<h3>Loaded Classes</h3>';
		$res .= '<p>';
		$classes = get_declared_classes();
		$res .= '<details><summary>' . count($classes) . ' ' . __('Classes') . '</summary>';
		$res .= pre($classes);
		$res .= '</details>';
		$res .= '</p>';

		$res .= '</td></tr></table>';

		$this->add(1, 'Dynamic', $res);

		/** Configs **/
		$infos = array('Settings', 'Config');
		if (!empty($options['configs'])) {
			$infos = array_merge($infos, $options['configs']);
		}
		$res = '';

		if (!empty($options['custom'])) {
			$res .= '<h3>Dynamic (controller/action)</h3>';
			foreach ($options['custom'] as $key => $val) {
				$res .= '<b>' . $key . ':</b>' . BR;
				$res .= pre(h($val)); #
			}
		}

		$res .= '<h3>Static (site wide)</h3>';

		foreach ($infos as $info) {
			$conf = Configure::read($info);
			if (!empty($conf)) {
				$res .= '<b>' . $info . ':</b>';
				$res .= pre(h($conf));
			}
		}

		/*
		// INCORRECT!!!
		$res .= '<h3>Controller-Components included</h3>';

		$x = Inflector::camelize($this->_View->request->params['controller']).'Controller';
		if (class_exists($x)) {
			$x = new $x(new CakeRequest, new CakeResponse);
		}
		if (isset($x) && is_object($x) && isset($x->components)) {
			$res .= pre($x->components);
		}
		*/

		//$res .= '<h3>Models used</h3>';
		//$res .= '<h3>Behaviours used</h3>';
		$res .= '<h3>Helpers included</h3>';
		$res .= pre($this->_View->helpers);

		$this->add(1, 'Configs', $res);

		/** Locales **/
		$infos = array('LC_TIME' => setlocale(LC_TIME, 0), 'LC_NUMERIC' => setlocale(LC_NUMERIC, 0), 'LC_MONETARY' => setlocale(LC_MONETARY, 0), 'LC_CTYPE' => setlocale(LC_CTYPE, 0), 'LC_COLLATE' => setlocale(LC_COLLATE, 0), 'LC_MESSAGES' => @setlocale(LC_MESSAGES, 0) . ' (only on some systems available)', // seems to run only on some systems
			);
		$res = '<h3>Locale stuff</h3><ul>';
		foreach ($infos as $name => $content) {
			$res .= '<li><b>' . $name . ':</b> ' . $content . '</li>';
		}
		$res .= '</ul><br />';

		$infos = array('date_default_timezone' => date_default_timezone_get(), 'time based on timezone' => date(FORMAT_NICE_YMDHMS), 'mb settings' => pre(mb_get_info()), );
		$res .= '<b>internal stuff:</b><ul>';
		foreach ($infos as $name => $content) {
			$res .= '<li><b>' . $name . ':</b> ' . $content . '</li>';
		}
		$res .= '</ul><br />';
		$this->add(1, 'Locales', $res);

		/** MISC + Quicklinks **/
		$res = '<table width="100%" style="table-layout:fixed"><tr><td style="padding-right:10px;">';

		$links = array('http://de.php.net/manual/en/book.strings.php' => 'PHP.net', 'http://www.tolleiv.de/fileadmin/sidekicks/commands/' => 'PHP Spickzettel',
			'http://www.addedbytes.com/download/mysql-cheat-sheet-v1/png/' => 'MySql Spickzettel', 'http://www.veign.com/downloads/guides/qrg0007.pdf' => 'CSS2 Spickzettel',
			'http://www.google.de/search?client=firefox-a&amp;rls=org.mozilla%3Ade%3Aofficial&amp;channel=s&amp;hl=de&amp;q=cheat+sheet&amp;meta=&amp;btnG=Google-Suche' => 'Google General CheatSheet', );
		$res .= 'Useful links:<ul>';
		foreach ($links as $link => $name) {
			$res .= '<li>' . $this->Html->link($name, $link) . '</li>';
		}
		$res .= '</ul><br />';

		$links = array('http://www.cakephp-forum.com/search.php?search_id=newposts' => 'German CakePHP-Forum', 'http://www.cakephpforum.net/index.php?act=Search&CODE=getnew' => 'English CakePHP-Forum', );
		$res .= 'CakeCommunity:<ul>';
		foreach ($links as $link => $name) {
			$res .= '<li>' . $this->Html->link($name, $link) . '</li>';
		}
		$res .= '</ul><br />';

		if (false && defined('HTTP_HOST') && HTTP_HOST !== 'localhost') {
			$res .= '<b>XHTML / CSS Validation</b><ul>';
			$res .=
				'<li><a href="http://validator.w3.org/check?uri=referer" target="_blank"><img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0?" height="26" title="check on it" /></a> <a href="http://jigsaw.w3.org/css-validator/validator?uri=' .
				$this->Html->url('/', true) . CSS_URL . $this->_View->layout .
				'.css&warning=2&profile=css3&usermedium=all" target="_blank"><img src="http://www.w3.org/Icons/valid-css" title="check on it" alt="Valid CSS?" height="26" /></a>
				</li>';
			$res .= '</ul>';
		}

		$res .= '</td><td>';

		$res .= 'XSS-Protection:<ul>';
		$strings = array('\';alert(String.fromCharCode(88, 83, 83))//\';alert(String.fromCharCode(88, 83, 83))//";alert(String.fromCharCode(88, 83, 83))//\";alert(String.fromCharCode(88, 83, 83))//--></SCRIPT>">\'><SCRIPT>alert(String.fromCharCode(88, 83, 83))</SCRIPT>',
			'<SCRIPT SRC=http://ha.ckers.org/xss.js></SCRIPT>');

		foreach ($strings as $string) {
			$res .= '<li><div style="border:1px solid yellow; padding:2px;">' . h($string) . '</div></li>';
		}
		$res .= '</ul><br />';

		$res .= '</td></tr></table>';

		$this->add(2, 'Misc', $res);

		// Add SQL tab
		$this->add(1, 'SQL-Log', $this->_View->element('sql_dump'));
	}

	protected function _opCodeCache() {
		App::uses('OpCodeCacheLib', 'Setup.Lib');
		if (OpCodeCacheLib::isEnabled()) {
			return true;
		}
		return false;
	}

	protected function _xdebug() {
		$v = phpversion('xdebug');
		//$v = ini_get('xdebug.coverage_enable');
		return $v;
	}

	/**
	 * DebugHelper::versionDB()
	 *
	 * @return string
	 */
	public function versionDB() {
		$configuration = null;
		$Model = (!empty($this->model) ? $this->model : 'Setup.Configuration');
		if (App::import('Model', $Model)) {
			$configuration = ClassRegistry::init($Model);
		}

		if (!is_object($configuration)) {
			return '- n/a -';
		}
		$dbV = $configuration->query('select version() as version'); # DateBase Version?
		$dbV = $dbV[0][0]['version'];
		//$dbVSplits = (strpos($dbV, '-') !== null ? explode('-', $dbV) : array($dbV));
		//$dbVNumeric = $dbVSplits[0];

		$version = '<b>' . $dbV . '</b>';
		return $version;
	}

	/**
	 * DebugHelper::versionPHP()
	 *
	 * @return string
	 */
	public function versionPHP() {
		$current = phpversion();

		$version = '<b>PHP ' . $current . '</b>';
		return $version;
	}

	/**
	 * Show both current and latest cake version (if activated in configs)
	 *
	 * @return string
	 */
	public function versionCake() {
		$current = Configure::version();

		$version = '<b>' . $current . '</b>';
		return $version;
	}

}
