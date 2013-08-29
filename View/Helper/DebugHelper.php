<?php
App::uses('AppHelper', 'View/Helper');
App::uses('DebugLib', 'Setup.Lib');
App::uses('CakeNumber', 'Utility');
App::uses('File', 'Utility');

if (!defined('BR')) {
	define('BR', '<br />');
}
// only used in debug mode! needs to be started manually!!!

/**
 * A helper to display a debug bar at the bottom of each page to quickly tab through all debug output.
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.0
 * 2011-11-20 ms
 */
class DebugHelper extends AppHelper {

	public $helpers = array('Html', 'Session', 'Tools.Datetime'); // this needs to be started manually, as well

	protected $level = 0;

	protected $debugContent = array('1' => array(), '2' => array(), '3' => array());

	protected $Model = '';

	protected $rememberMe = false; //cookie or ajax (= session)

	protected $rememberEngine = 'cookie';

	public $packages = array('Tools.AppJs::debug');

	public function __construct(View $View, $level = null, $options = array()) {
		parent::__construct($View, $options);

		$this->_ViewProperties = $this->_objectToArray($View);

		if (!empty($options['model'])) {
			$this->_useModel($options['model']);
		}

		$this->level = (int)$level;
		$this->_debug($options);

		if (Configure::read('Debug.ajax_remember')) {
			$this->rememberMe = true;
			$this->rememberEngine = 'ajax';
		} elseif (Configure::read('Debug.cookie_remember')) {
			$this->rememberMe = true;
		} elseif (Configure::read('Debug.remember')) {
			$this->rememberMe = true;
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
		$this->Model = $name;
	}

	/**
	 * Shows pr() messages, even with debug=0
	 *
	 * @access public (in the view via $html helper etc.)
	 * 2008-12-08 ms
	 */
	public function pre($array, $class = null, $escape = true) {
		$pre_array = '';
		$pre_class = '';

		if (!empty($class)) {
			$pre_class = $class;
		}
		$res = Debugger::exportVar($array, 10);
		if ($escape) {
			$res = h($res);
		}
		return '<pre '.$pre_class.'>'.nl2br($res).'</pre>';
	}

	/**
	 * change default "active/visible" tab
	 * 2008-12-12 ms
	 */
	public function setDefault() {

	}

	/**
	 * final print function
	 * 2008-12-12 ms
	 */
	public function show() {
		$output = '<div class="tabs cake-sql-log debug_request">';
		$header = '<ul class="tabNavigation">';
		$body = '';

		for ($i = 1; $i <= $this->level; $i++) {
			foreach ($this->debugContent[$i] as $title => $content) {
				if (!empty($content)) {
					$sluggedTitle = strtolower(Inflector::slug($title));
					$header .= '<li><a href="#debug-'.$sluggedTitle.'" id="tab-'.$sluggedTitle.'">'.$title.'</a></li>';
					$body .= '<div class="content" id="debug-'.$sluggedTitle.'">'.$content.'</div>';
				}
			}
		}
		$header .= '</ul>';

		$output .= $header.$body.'</div>';
		$output .= $this->Html->css('/setup/css/tabs');
		$output .= $this->Html->script('/setup/js/tabs');

		if ($this->rememberMe) {

			if ($this->rememberEngine === 'ajax') {
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

			if ($this->rememberEngine === 'ajax') {
				$script = '
	$(\'.tabNavigation li a\').click(function () {
		var selvalue = $(this).attr(\'id\');
		var targeturl = "'.$url.'tab:" + selvalue + "/";
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
	var remember = \''.$rememberedId.'\';

	' . $script . '

	' . (!empty($rememberedId) ? '

	jQuery(\'div.tabs ul.tabNavigation a#'.$rememberedId.'\').click();

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
	 * adds a new tab
	 * takes strings AND arrays now (automatic switch)
	 * 2009-01-10 ms
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
	 * sets normal default tabs
	 */
	protected function _debug($options = null) {
		$res = '<div class="globals">';
		$res .= '<div style="float:right">'.$this->Html->link('Error-Logs', array('plugin'=>'setup', 'admin'=>true, 'controller'=>'configuration', 'action'=>'logs')).'</div>';
		$res .= '<div style="float:right; margin-right: 20px;">'.$this->Html->link('Configuration', array('plugin'=>'setup', 'admin'=>true, 'controller'=>'configuration', 'action'=>'index')).'</div>';
		//$res .=  xdebug_time_index();
		$res .= 'Cake-Version: '.$this->versionCake() . BR . BR;
		$res .= __('rendered in %s', '<b>' . number_format(round(microtime(true) - $_SERVER['REQUEST_TIME'], 3), 3, ',', '') . ' s</b>') . BR;
		$res .= '</div>';
		$this->add(1, 'Start', $res);

		/** Session Post Get... **/
		$res = '<table width="100%"><tr><td>';
		$res .= '<div class="globals"><b>GET</b><br/><pre>'.h(print_r($_GET, true)).'</pre>';
		$res .= '</div>';
		$res .= '<div class="globals"><b>POST</b><br/><pre>'.h(print_r($_POST, true)).'</pre>';
		$res .= '</div>';
		$res .= '<div class="globals"><b>SESSION</b><br/><pre>'.h(print_r(!empty($_SESSION) ? $_SESSION : $this->Session->read(), true)).'</pre>';
		$res .= '</div>';
		$res .= '</td><td>';
		$res .= '<div class="globals"><b>REQUEST</b><br/><pre>'.h(print_r($_REQUEST, true)).'</pre>';
		$res .= '</div>';
		$res .= '<div class="globals"><b>COOKIE</b><br/><pre>'.h(print_r($_COOKIE, true)).'</pre></div>';

		if (!empty($_FILES)) {
			$res .= '<div class="globals"><b>FILES</b><br/><pre>'.h(print_r($_FILES, true)).'</pre></div>';
		}

		$res .= '</td></tr></table>';
		$this->add(1, '$_VARS', $res);

		/** this->data... **/
		//$res = '<table width="100%"><tr><td>';
		//<b>this-&gt;data</b><br/>
		$res = '<div class="globals"><pre>'.h(print_r($this->_View->data, true)).'</pre>';
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
		# play nice with DebugKit
		if (isset($viewVars['debugToolbarPanels'])) {
			unset($viewVars['debugToolbarPanels']);
		}
		if (isset($viewVars['debugToolbarJavascript'])) {
			unset($viewVars['debugToolbarJavascript']);
		}
		$viewVars = array_reverse($viewVars);

		$res = '<table width="100%"><tr><td>';
		$res .= '<div class="globals"><b>View Vars</b><br/>'.$this->pre($viewVars).'';
		$res .= '</div>';
		$res .= '</td></tr></table>';
		$this->add(1, 'ViewVars', $res);

		/** View parameters **/
		$res = '<table width="100%" style="table-layout:fixed;"><tr><td style="padding-right:10px;">';

		$res .= '<h3>Params</h3>';
		$res .= '<b>View parameters</b><br /><br />';
		$res .= '$this->request->base: '.(!empty($this->_View->base) ? $this->_View->base : '<i>n/a</i>').'<br/>';
		$res .= '$this->request->here: '.(!empty($this->_View->here) ? $this->_View->here : '<i>n/a</i>').'<br/>';
		$res .= '$this->name: '.(!empty($this->_View->name) ? $this->_View->name : '<i>n/a</i>').'<br/>';
		$res .= '$this->_ViewPath: '.(!empty($this->_View->viewPath) ? $this->_View->viewPath : '<i>n/a</i>').'<br/>';
		$res .= '$this->themePath: '.(!empty($this->_View->themePath) ? $this->_View->themePath : '<i>n/a</i>').'<br/>';
		$res .= '$this->request->action: '.(!empty($this->_View->action) ? $this->_View->action : '<i>n/a</i>').'<br/>';
		$res .= '$this->ext: '.(!empty($this->_View->ext) ? $this->_View->ext : '<i>n/a</i>').'<br/>';
		$res .= '$this->layout: '.(!empty($this->_View->layout) ? $this->_View->layout : '<i>n/a</i>').'<br/>';
		$res .= '$this->uses: '.(!empty($this->_View->uses) ? $this->_View->uses : '<i>n/a</i>').'<br/>';
		$res .= '$this->validationErrors: '.(!empty($this->_View->validationErrors) ?'<pre class="">'.h(print_r($this->_View->validationErrors, true)).'</pre>' : '<i>n/a</i>').'<br/>';
		$res .= '$this->pageTitle: '.(!empty($this->_View->pageTitle) ? $this->_View->pageTitle : '<i>n/a</i>').'<br/>';
		$res .= '$this->parent: '.(!empty($this->_View->parent) ? $this->_View->parent : '<i>n/a</i>').'<br/>';
		$res .= '<br /><br />';

		/** URL **/
		$res .= '<b>Url Resolving</b><br /><br />';
		$res .= '$this->request->params[\'prefix\']: '.(!empty($this->_View->request->params['prefix']) ?h($this->_View->request->params['prefix']) : '<i>n/a</i>').'<br/>';
		$res .= '$this->request->params[\'admin\']: '.(!empty($this->_View->request->params['admin']) ?h($this->_View->request->params['admin']) : '<i>n/a</i>').'<br/>';
		$res .= '$this->request->params[\'plugin\']: '.(!empty($this->_View->request->params['plugin']) ?h($this->_View->request->params['plugin']) : '<i>n/a</i>').'<br/>';

		$res .= '$this->request->params[\'controller\']: '.(!empty($this->_View->request->params['controller']) ?h($this->_View->request->params['controller']) : '<i>n/a</i>').'<br/>';
		$res .= '$this->request->params[\'action\']: '.(!empty($this->_View->request->params['action']) ?h($this->_View->request->params['action']) : '<i>n/a</i>').'<br/>';

		if (!empty($this->_View->request->params['pass'])) {
			$res .= '$this->request->params[\'pass\']:'.pre(h($this->_View->request->params['pass']));
		}

		if (!empty($this->_View->request->params['named'])) {
			$res .= '$this->request->params[\'named\']:'.pre(h($this->_View->request->params['named']));
		}
		if (!empty($this->_View->request->params['ext'])) {
			$res .= '$this->request->params[\'ext\']:'.pre(h($this->_View->request->params['ext']));
		}
		if (isset($this->_View->request->query)) {
			$res .= '$this->request->query:'.pre(h($this->_View->request->query));
		}
		if (!empty($this->_View->request->params['isAjax'])) {
			$res .= '$this->request->params[\'isAjax\']:'.pre(h($this->_View->request->params['isAjax']));
		}
		if (!empty($this->_View->request->params['models'])) {
			$res .= '$this->request->params[\'models\']: '.pre($this->_View->request->params['models']).'<br/>';
		}

		$res .= '</td><td>';

		/** Dynamic Stuff **/
		$res .= '<h3>Live</h3>';
		$res .= '<p>';

		$res .= 'session_id(): '.session_id().'<br />';
		$res .= 'time(): '.time().' ('.$this->Datetime->niceDate(time()).')'.BR;

		$res .= 'Session Expiration: '.(!empty($_SESSION['Config']['time']) ?date(FORMAT_NICE_YMDHMS, $_SESSION['Config']['time']):'---').BR;
		$res .= '</p>';

		$res .= '<h3>Environment</h3>';
		$res .= '<p>';
		$res .= 'env(\'HTTP_HOST\'): '.env('HTTP_HOST').' (official referer method)<br />';
		$res .= 'env(\'HTTP_REFERER\'): '.env('HTTP_REFERER').' (not to trust!)<br />';

		$res .= 'env(\'CGI_MODE\'): '.env('CGI_MODE').'<br />';
		$res .= 'env(\'PHP_SELF\'): '.env('PHP_SELF').'<br />';
		$res .= 'env(\'HTTP_BASE\'): '.env('HTTP_BASE').'<br />';
		$res .= 'env(\'DOCUMENT_ROOT\'): '.env('DOCUMENT_ROOT').'<br />';

		$res .= 'env(\'SCRIPT_NAME\'): '.env('SCRIPT_NAME').'<br />';
		$res .= 'env(\'REDIRECT_QUERY_STRING\'): '.env('REDIRECT_QUERY_STRING').'<br />';
		$res .= 'env(\'REDIRECT_URL\'): '.env('REDIRECT_URL').'<br />';
		$res .= 'URL length: '.(mb_strlen(env('REDIRECT_URL')) + mb_strlen(env('REDIRECT_QUERY_STRING'))). ' (of 2,048)<br />';

		$res .= 'env(\'HTTPS\'): '.env('HTTPS').'<br />';

		$res .= 'env(\'REMOTE_ADDR\'): '. ($ip = env('REMOTE_ADDR')).'<br />';
		$res .= 'gethostbyaddr(env(\'REMOTE_ADDR\')): ' . ($ip ? gethostbyaddr($ip) : '' ) . '<br />';
		$res .= 'env(\'HTTP_USER_AGENT\'): '.env('HTTP_USER_AGENT').'<br />';

		$res .= '</p>';

		$res .= '<h3>System</h3>';

		//$res .= 'Lazy Loading: '.($this->_lazyLoading() ? 'JA' : 'NEIN').'<br />';
		$res .= 'Opcode Cache: '.($this->_opCodeCache() ? 'JA' : 'NEIN').'<br />';
		$res .= 'XDebug: '.($this->_xdebug() ? 'JA' : 'NEIN').'<br />';
		$res .= 'Memory Usage: '.CakeNumber::toReadableSize(DebugLib::memoryUsage()).' (Peak: '.CakeNumber::toReadableSize(DebugLib::peakMemoryUsage()).')';
		$res .= '<br />';
		$res .= 'PHP-Version: '.$this->versionPHP().'<br />';
		# currently: just read out "http://www.php.net/downloads.php -> <h2>...</h2>"

		$res .= 'DB-Version: '.$this->versionDB().'';
		$res .= '</p>';

		$res .= '<p>';
		$configObject = new DATABASE_CONFIG();
		if (method_exists($configObject, 'current')) {
			$currentConfig = $configObject->current(true);
			if (is_array($currentConfig)) {
				$currentConfig = pre($currentConfig);
			}
			$res .= 'Database Config: '.$currentConfig;
			$res .= '</p>';
			$res .= '<p>';
		}

		$res .= '<u>Core Config:</u><br />';
		$res .= 'Security Level: '.Configure::read('Security.level').'<br />';
		$settings = Cache::settings();
		$res .= 'Cache-Engine: '.(!empty($settings['engine']) ? $settings['engine'] : '<b>NONE</b>').'<br />';
		if (!empty($settings['engine']) && $settings['engine'] === 'File') {
			$res .= 'Cache-Folder writable: ' . (is_writable(TMP) ? 'YES' : '<b>NO</b>') . '<br />';
		}
		$res .= '</p>';

		$res .= '<u>Other:</u>';
		$res .= '<p>';
		# if salt and cipher key are altered in core.php
		Debugger::checkSecurityKeys();
		$res .= '</p>';

		$res .= '<h3>Loaded Files</h3>';
		$res .= '<p>';
		$files = get_included_files();
		foreach ($files as $key => $val) {
			$files[$key] = str_replace(ROOT, '', $val);
		}
		$res .= '<details><summary>'.count($files).' '.__('Files').'</summary>';
		$res .= pre($files);
		$res .= '</details>';
		$res .= '</p>';

		$res .= '<h3>Loaded Classes</h3>';
		$res .= '<p>';
		$classes = get_declared_classes();
		$res .= '<details><summary>'.count($classes).' '.__('Classes').'</summary>';
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
				$res .= '<b>'.$key.':</b>'.BR;
				$res .= pre(h($val)); #
			}
		}

		$res .= '<h3>Static (site wide)</h3>';

		foreach ($infos as $info) {
			$conf = Configure::read($info);
			if (!empty($conf)) {
				$res .= '<b>'.$info.':</b>';
				$res .= pre(h($conf));
			}
		}

		/*
		# INCORRECT!!!
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
		$infos = array('LC_TIME' => setlocale(LC_TIME, 0), 'LC_NUMERIC' => setlocale(LC_NUMERIC, 0), 'LC_MONETARY' => setlocale(LC_MONETARY, 0), 'LC_CTYPE' => setlocale(LC_CTYPE, 0), 'LC_COLLATE' => setlocale
			(LC_COLLATE, 0), 'LC_MESSAGES' => @setlocale(LC_MESSAGES, 0).' (only on some systems available)', // seems to run only on some systems
			);
		$res = '<h3>Locale stuff</h3><ul>';
		foreach ($infos as $name => $content) {
			$res .= '<li><b>'.$name.':</b> '.$content.'</li>';
		}
		$res .= '</ul><br />';

		$infos = array('date_default_timezone' => date_default_timezone_get(), 'time based on timezone' => date(FORMAT_NICE_YMDHMS), 'mb settings' => pre(mb_get_info()), );
		$res .= '<b>internal stuff:</b><ul>';
		foreach ($infos as $name => $content) {
			$res .= '<li><b>'.$name.':</b> '.$content.'</li>';
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
			$res .= '<li>'.$this->Html->link($name, $link).'</li>';
		}
		$res .= '</ul><br />';

		$links = array('http://www.cakephp-forum.com/search.php?search_id=newposts' => 'German CakePHP-Forum', 'http://www.cakephpforum.net/index.php?act=Search&CODE=getnew' => 'English CakePHP-Forum', );
		$res .= 'CakeCommunity:<ul>';
		foreach ($links as $link => $name) {
			$res .= '<li>'.$this->Html->link($name, $link).'</li>';
		}
		$res .= '</ul><br />';

		if (false && defined('HTTP_HOST') && HTTP_HOST !== 'localhost') {
			$res .= '<b>XHTML / CSS Validation</b><ul>';
			$res .=
				'<li><a href="http://validator.w3.org/check?uri=referer" target="_blank"><img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0?" height="26" title="check on it" /></a> <a href="http://jigsaw.w3.org/css-validator/validator?uri='.
				$this->Html->url('/', true) . CSS_URL . $this->_View->layout.
				'.css&warning=2&profile=css3&usermedium=all" target="_blank"><img src="http://www.w3.org/Icons/valid-css" title="check on it" alt="Valid CSS?" height="26" /></a>
				</li>';
			$res .= '</ul>';
		}

		$res .= '</td><td>';

		$res .= 'XSS-Protection:<ul>';
		$strings = array('\';alert(String.fromCharCode(88, 83, 83))//\';alert(String.fromCharCode(88, 83, 83))//";alert(String.fromCharCode(88, 83, 83))//\";alert(String.fromCharCode(88, 83, 83))//--></SCRIPT>">\'><SCRIPT>alert(String.fromCharCode(88, 83, 83))</SCRIPT>',
			'<SCRIPT SRC=http://ha.ckers.org/xss.js></SCRIPT>');

		foreach ($strings as $string) {
			$res .= '<li><div style="border:1px solid yellow; padding:2px;">'.h($string).'</div></li>';
		}
		$res .= '</ul><br />';

		$res .= '</td></tr></table>';

		$this->add(2, 'Misc', $res);

		# Add SQL on Cake >= 1.3
		$this->add(1, 'SQL-Log', $this->_View->element('sql_dump'));

		//$res = '<table class="cake-sql-log debug_request"><tr><td>'.$cakeDebug.'</td></tr></table>';
		//$this->add(3, 'SQL', $res);	// usually printed out right away (needs core hack!!!)

	}

	protected function _opCodeCache() {
		App::uses('OpCodeCacheLib', 'Setup.Lib');
		if (OpCodeCacheLib::isEnabled()) {
			return true;
		}
		return false;
	}

	protected function _xdebug() {
		//return version_compare(phpversion('xdebug'), '2.2.0-dev', '>='
		$v = phpversion('xdebug');
		//$v = ini_get('xdebug.coverage_enable');
		return $v;
	}

	/**
	 * returns version if newer than the current one
	 * NEW: use CACHED version if available (to save time)
	 * @return string $version on SUCCESS, FALSE if could not find out, NULL if deactivated in config
	 */
	public function retrieveLatestPHP() {
		$url = 'http://www.php.net/downloads.php';
		$retrieve = Configure::read('Debug.check_for_php_version');
		if (empty($retrieve)) {
			return null;
		}

		# cache retrieval!!!
		$handle = new File(CACHE.'persistent'.DS.'version_php.txt', true);
		if (!$handle->exists() || !$handle->writable()) {
			$this->log('cache not writable', 'error');
			return false;
		}
		$cacheChange = $handle->lastChange();
		$cacheContent = $handle->read();
		//pr (time()-$cacheChange);
		if (!empty($cacheContent) && (time() - $cacheChange) < 2 * 24 * 3600) { # 2daily updated
			# use cached content
			return $cacheContent;
		}

		# no cache, so get info and save to cache afterwards
		$file = $this->readOutForeignPage($url, '<h2>', '</h2>');
		if (!empty($file)) {
			$handle->write($file, 'w', true);
			return h($file);
		}

		return false;
	}

	/**
	 * returns version if newer than the current one
	 * NEW: use CACHED version if available (to save time)
	 * @return string $version on SUCCESS, FALSE if could not find out, NULL if deactivated in config
	 */
	public function retrieveLatestMYSQL() {
		$url = 'http://dev.mysql.com/downloads/mysql/'; // http://dev.mysql.com/downloads/mysql/5.1.html
		$retrieve = Configure::read('Debug.check_for_mysql_version');
		if (empty($retrieve)) {
			return null;
		}

		# cache retrieval!!!
		$handle = new File(CACHE.'persistent'.DS.'version_mysql.txt', true);
		if (!$handle->exists() || !$handle->writable()) {
			$this->log('cache not writable', 'error');
			return false;
		}
		$cacheChange = $handle->lastChange();
		$cacheContent = $handle->read();
		//pr (time()-$cacheChange);
		if (!empty($cacheContent) && (time() - $cacheChange) < 2 * 24 * 3600) { # 2daily updated
			# use cached content
			return $cacheContent;
		}

		# no cache, so get info and save to cache afterwards
		$file = $this->readOutForeignPage($url, '<td class="dlcol3">', '</td>');
		if (!empty($file)) {
			$handle->write($file, 'w', true);
			return h($file);
		}

		return false;
	}

	/**
	 * returns version if newer than the current one
	 * NEW: use CACHED version if available (to save time)
	 * @return string $version on SUCCESS, FALSE if could not find out, NULL if deactivated in config
	 */
	public function retrieveLatestStable() {
		$url = 'http://cakeforge.org/projects/cakephp/';
		$retrieve = Configure::read('Debug.check_for_cake_version');
		if (empty($retrieve)) {
			return null;
		}

		# cache retrieval!!!
		$handle = new File(CACHE.'persistent'.DS.'version_cake.txt', true);
		if (!$handle->exists() || !$handle->writable()) {
			$this->log('cache not writable', 'error');
			return false;
		}
		$cacheChange = $handle->lastChange();
		$cacheContent = $handle->read();
		//pr (time()-$cacheChange);
		if (!empty($cacheContent) && (time() - $cacheChange) < 24 * 3600) { # daily updated
			# use cached content
			return h($cacheContent);
		}

		# no cache, so get info and save to cache afterwards
		$file = $this->readOutForeignPage($url, '<strong>Stable</strong></td><td>', '<');
		if (!empty($file)) {
			$handle->write($file, 'w', true);
			return h($file);
		}
		/*
		$file = file_get_contents($url);
		$file = strstr($file, '<table cellspacing="8" cellpadding="6" border="0">');
		if (!empty($file)) {
		//pr (substr($file, 0, 100))
		$file = strstr($file, '<strong>Stable</strong></td><td>');
		if (!empty($file)) {
		$file = substr($file, mb_strlen('<strong>Stable</strong></td><td>'), 20);
		if (!empty($file)) {
		$pos = strpos($file, '<');
		$file = trim(substr($file, 0, $pos));
		if (!empty($file)) {
		return h($file);
		}
		}
		}

		}
		*/
		return false;
	}

	/**
	 * @return FALSE on failure, otherwise the found "content"
	 * //TODO rewrite (without fopen etc)
	 * 2009-04-05 ms
	 */
	public function readOutForeignPage($url, $start = '', $end = '') {
		$file = @fopen($url, "r");

		if ($file == null || trim((String)$file) === '') {
			//echo "Service out of order";
			return false;
		}
		$i = 0;
		while (!feof($file)) {

			// Wenn das File entsprechend groß ist, kann es unter Umständen
			// notwendig sein, die Zahl 2000 entsprechend zu erhöhen. Im Falle
			// eines Buffer-Overflows gibt PHP eine entsprechende Fehlermeldung aus.

			$row[$i] = fgets($file, 2000);
			$i++;
		}
		fclose($file);

		// Nun werden die Daten entsprechend gefiltert.
		$result = null;
		for ($j = 0; $j < $i; $j++) {
			if ($resa = strstr($row[$j], $start)) {
				$resb = str_replace($start, "", $resa);
				$endpiece = strstr($resb, $end);
				$result = str_replace($endpiece, "", $resb);
			}
		}
		return trim($result);
	}

	public function versionDB() {
		$configuration = null;
		$Model = (!empty($this->Model) ? $this->Model : 'Setup.Configuration');
		if (App::import('Model', $Model)) {
			$configuration = ClassRegistry::init($Model);
		}

		if (!is_object($configuration)) {
			return '- n/a -';
		}

		$dbV = $configuration->query('select version() as version'); # DateBase Version?
		$dbV = $dbV[0][0]['version'];
		$dbVSplits = (strpos($dbV, '-') !== null?explode('-', $dbV) : array($dbV));
		$dbVNumeric = $dbVSplits[0];

		$new = $this->retrieveLatestMYSQL();

		if ($new === false) {
			$newText = '<b>n/a</b> (could not be retrieved)';
		} elseif ($new === null) {
			$newText = '<b>n/a</b> (deactivated in config)';
		} elseif (!empty($new) && $dbVNumeric == $new) {
			$newText = '<b>same :-)</span></b>';
		} elseif (!empty($new)) {
			$newText = '<b><span class="latestVersionWarning">'.$new.'</span></b>';
		}

		$version = '<b>'.$dbV.'</b>';
		$version .= ' &nbsp;|&nbsp; latest stable: '.$newText.'';

		return $version;
	}

	public function versionPHP() {
		$current = phpversion();
		$new = $this->retrieveLatestPHP();
		$newNumeric = (strlen($new) > 4?substr($new, 4) : $new);

		if ($new === false) {
			$newText = '<b>n/a</b> (could not be retrieved)';
		} elseif ($new === null) {
			$newText = '<b>n/a</b> (deactivated in config)';
		} elseif (!empty($new) && $current == $newNumeric) {
			$newText = '<b>same :-)</span></b>';
		} elseif (!empty($new)) {
			$newText = '<b><span class="latestVersionWarning">'.$new.'</span></b>';
		}

		$version = '<b>PHP '.$current.'</b>';
		$version .= ' &nbsp;|&nbsp; latest stable: '.$newText.'';

		return $version;
	}

	/**
	 * show both current and latest cake version (if activated in configs)
	 * 2009-05-15 ms
	 */
	public function versionCake() {
		//$current = Configure::read('Cake.version');
		$current = Configure::version();

		$new = $this->retrieveLatestStable();
		if ($new === false) {
			$newText = '<b>n/a</b> (could not be retrieved)';
		} elseif ($new === null) {
			$newText = '<b>n/a</b> (deactivated in config)';
		} elseif (!empty($new) && $current == $new) {
			$newText = '<b>same :-)</span></b>';
		} elseif (!empty($new)) {
			$newText = '<b><span class="cakeLatestVersionWarning">'.$new.'</span></b>';
		}

		$version = '<b>'.$current.'</b>';
		$version .= ' &nbsp;|&nbsp; latest stable: '.$newText.'';

		return $version;
	}

}
