<?php
App::uses('SetupAppController', 'Setup.Controller');
/**
 * For xss security:
 * http://ha.ckers.org/xssAttacks.xml
 *
 */
class TestsController extends SetupAppController {

	public $components = array('Cookie');

	public $uses = array('Configuration');

	public $DebugLib = null;

	public function beforeFilter() {
		parent::beforeFilter();

		if (isset($this->Auth)) {
			$this->Auth->allow('index', 'access', 'access_results', 'is_mobile', 'html5', 'html5_elements', 'validation', 'text_as_image', 'admin_old_browser_alert_test', 'admin_sound', 'admin_test_mail');
		}

		// temp:
		//$this->Auth->allow();

		//App::uses('DebugLib', 'Setup.Lib');
		//$this->DebugLib = new DebugLib();
	}

	public function __construct(CakeRequest $request, CakeResponse $response) {
		if (($table = Configure::read('Configuration.table')) !== null) {
			if ($table === false) {
				$this->uses = array();
			}
		}
		parent::__construct($request, $response);
	}

/*** user ***/

	public function index() {
	}

	public function text_as_image() {
		//$this->Common->loadHelper('Tools.Format');
	}

	public function html5() {
		// unfortunately there is no layout=Tools.mobile
		$this->plugin = 'Tools';
		$this->layout = 'mobile';
		$this->viewPath = '..' . DS . '..' . DS . 'Setup' . DS . 'View' . DS . 'Tests';
		//$this->layoutPath = CakePlugin::path('Tools').'View'.DS.'Layouts';

		//$this->layoutPath = '..'.DS.'..'.DS.'Tools'.DS.'View'.DS.'Layouts';
	}

	public function html5_elements() {
		$this->plugin = 'Tools';
		$this->layout = 'mobile';
		$this->viewPath = '..' . DS . '..' . DS . 'Setup' . DS . 'View' . DS . 'Tests';
	}

	public function admin_post_link() {
	}

	/**
	 * Check what a browser / curl access leaves behind
	 */
	public function access() {
		if (!isset($this->RequestHandler)) {
			$this->Common->loadComponent(array('RequestHandler'));
		}

		$res = array();

		$server = array();
		$whitelist = array('REDIRECT_STATUS', 'HTTP_CONNECTION', 'HTTP_ACCEPT', 'HTTP_USER_AGENT', 'HTTP_ACCEPT_ENCODING', 'HTTP_ACCEPT_LANGUAGE', 'HTTP_ACCEPT_CHARSET', 'HTTP_COOKIE', 'REQUEST_METHOD', 'REMOTE_PORT', 'REDIRECT_URL', 'REDIRECT_QUERY_STRING', 'GATEWAY_INTERFACE', 'SERVER_PROTOCOL', 'REQUEST_METHOD', 'REQUEST_TIME');
		foreach ($whitelist as $element) {
			if (!array_key_exists($element, $_SERVER)) {
				continue;
			}
			$server[$element] = $_SERVER[$element];
		}

		$res['time'] = time();
		$res['ip'] = $this->request->clientIp();
		$res['host'] = gethostbyaddr($res['ip']);
		$res['referer'] = $this->request->referer();
		$res['session'] = session_id();

		if ($res['ip'] !== '127.0.0.1') {
			App::uses('GeolocateLib', 'Tools.Lib');
			$geoLocate = new GeolocateLib();
			$geoLocate->locate($res['ip']);
			$res['geodata'] = $geoLocate->getResult();
		} else {
			$res['geodata'] = array();
		}

		$details = array();
		$details['isMobile'] = (int)$this->RequestHandler->isMobile();
		$details['isPost'] = (int)$this->request->is('post');
		$details['isAjax'] = (int)$this->request->is('ajax');
		$details['ajaxVersion'] = $this->RequestHandler->getAjaxVersion();
		$details['isGet'] = (int)$this->request->is('get');
		$details['isXml'] = (int)$this->RequestHandler->isXml();

		$details['accepts'] = $this->RequestHandler->accepts();
		$details['requestedWith'] = $this->RequestHandler->requestedWith();
		$details['prefers'] = $this->RequestHandler->prefers();
		$res['details'] = $details;

		$res['params'] = $this->request->params;
		$res['data'] = $this->request->data;
		$res['get'] = $_GET;
		$res['post'] = $_POST;
		$res['server'] = $server;

		if (!file_exists(LOGS . 'access')) {
			mkdir(LOGS . 'access', 0755);
		}

		$i = '';
		$name = 'access_' . time();
		while (file_exists(LOGS . 'access' . DS . $name . (!empty($i) ? '_' . $i : '') . '.txt')) {
			$i = (int)$i + 1;
		}
		file_put_contents(LOGS . 'access' . DS . $name . (!empty($i) ? '_' . $i : '') . '.txt', serialize($res));

		die('DONE');
	}

	/**
	 * Display logged information
	 */
	public function access_results() {
		App::uses('Folder', 'Utility');
		$folder = new Folder(LOGS . 'access', true);
		$files = $folder->read(true, true);
		$files = $files[1];
		$files = array_reverse($files); # newest ones first!

		$results = array();
		$count = 0;
		foreach ($files as $file) {
			$content = file_get_contents(LOGS . 'access' . DS . $file);
			if (empty($content)) {
				continue; # delete file?
			}
			$results[] = h(unserialize($content));
			$count++;
			if ($count >= 20) { # only the 20 newest files
				break;
			}
			//TODO: cleanup (remove all older ones)
		}
		//pr($results); die();

		$this->set(compact('results'));
		$this->Common->loadHelper('Setup.Test');
	}

	public function validation() {
		$validationErrors = array();

		if (!isset($this->Configuration)) {
			$this->Configuration = ClassRegistry::init('Setup.Configuration');
		}

		$this->Configuration->validate = array(
			'required_string' => array(
				'maxLength' => array(
					'required' => true,
					'rule' => array('maxLength', 5),
					'message' => array('valErrMaxCharacters %s', 5),
					'last' => true
				)
			),
			'not_allowed_empty_string' => array(
				'maxLength' => array(
					'allowEmpty' => false,
					'rule' => array('maxLength', 5),
					'message' => array('valErrMaxCharacters %s', 5),
					'last' => true
				)
			),
			'required_string_gone' => array(
				'maxLength' => array(
					'required' => true,
					'rule' => array('maxLength', 5),
					'message' => array('valErrMaxCharacters %s', 5),
					'last' => true
				)
			),
			'not_allowed_empty_string_gone' => array(
				'maxLength' => array(
					'allowEmpty' => false,
					'rule' => array('maxLength', 5),
					'message' => array('valErrMaxCharacters %s', 5),
					'last' => true
				)
			),
		);

		if ($this->Common->isPosted()) {
			$this->Configuration->set($this->request->data);
			if ($this->Configuration->validates()) {
				$this->Common->flashMessage('OK', 'success');
			} else {
				$validationErrors = $this->Configuration->validationErrors;
				$this->Common->flashMessage('NOT OK', 'error');
			}

		}

		$this->set(compact('validationErrors'));
	}

	public function admin_form() {
		$required = array(
			'numeric' => array(
				'allowEmpty' => false,
				'rule' => array('numeric'),
				'message' => array('valErrMandatoryField'),
				'last' => true
			)
		);
		$allowedEmpty = array(
			'numeric' => array(
				'allowEmpty' => true,
				'rule' => array('numeric'),
				'message' => array('valErrMandatoryField'),
				'last' => true
			)
		);
		$validationErrors = array();

		if (!isset($this->Configuration)) {
			$this->Configuration = ClassRegistry::init('Setup.Configuration');
		}

		$this->Configuration->validate = array(
			'radio' => $required,
			'radio_optional' => $allowedEmpty,
			'checkbox' => $required,
			'checkbox_optional' => $allowedEmpty,
			'select' => $required,
			'select_optional' => $allowedEmpty,
		);

		if ($this->Common->isPosted()) {
			$this->Configuration->set($this->request->data);
			if ($this->Configuration->validates()) {
				$this->Common->flashMessage('OK', 'success');
			} else {
				$validationErrors = $this->Configuration->validationErrors;
				$this->Common->flashMessage('NOT OK', 'error');
			}

		}
		$this->set(compact('validationErrors'));
	}

	/**
	 * Detect if accessed via mobile phone
	 */
	public function is_mobile() {
		$result = $this->RequestHandler->isMobile();
		$userAgent = env('HTTP_USER_AGENT');
		$this->set(compact('result', 'userAgent'));
	}

	/**
	 * Honeypot:
	 * only malconfigured bots!!!
	 */
	public function hp() {
	$this->_checkAgainstBots();
	die();
	}

	/**
	 * Noscript:
	 * could be any visitor without js or bot
	 */
	public function ns() {
	$this->_checkAgainstBots();
	die();
	}

	public function _checkAgainstBots() {
	App::import('Component', 'BotDetection');
	$this->BotDetection = new BotDetectionComponent();
	$this->BotDetection->setLog(true);
	$this->BotDetection->startup($this);
	$this->BotDetection->check(env('HTTP_USER_AGENT'), $this->request->params['action']);
	}

/*** admin ***/

	/**
	 * Retrieve image meta infomation
	 */
	public function admin_image() {

		$image = array();
		if (!empty($this->request->data['Test']['file']['tmp_name'])) {

			$file = $this->request->data['Test']['file']['tmp_name'];

			$image['size'] = @getimagesize($file);

			if (function_exists('exif_read_data')) {
				$exif = @exif_read_data($file, 'ANY_TAG', true);
				if ($exif !== false) {
					$image['exif'] = $exif;
				} else {
					$image['exif'] = array();
				}
			}
			if (!empty($image['size'][0]) && !empty($image['size'][1])) {
				// further information and tests etc
				App::uses('ExifLib', 'Tools.Lib');
				$exifLib = new ExifLib();
				$image['exif_lib'] = $exifLib->read($file);

				App::import('Component', 'Uploader.Uploader');
				$this->Uploader = new UploaderComponent();
				$this->Uploader->uploadDir = 'img/content/tmp/';

				$this->Uploader->initialize($this);
				$this->Uploader->startup();

				//$this->Uploader->validFileTypes(array('image'=>array('jpg', 'gif', 'png')));
				if (($data = $this->Uploader->upload('file', array('overwrite' => true, 'name' => 'original', 'resize' => true, 'size' => '500'))) && !empty($data['width']) && !empty($data['height'])) {
					$image['upload'] = $data;
				}

				$max = 500;
				$res = $this->Uploader->resize(array('width' => $max, 'height' => $max, 'append' => false, 'overwrite' => false, 'name' => 'resized', 'save_as' => 'jpg'));
				$image['resize'] = $res;
				$min = 90;
				$res = $this->Uploader->crop(array('width' => $min, 'height' => $min, 'append' => false, 'name' => 'cropped', 'overwrite' => true, 'save_as' => 'jpg'));
				$image['crop'] = $res;

			}
		}

		$this->set(compact('image'));
	}

	public function admin_image2() {
		$folder = TMP . 'image' . DS;
		App::uses('Folder', 'Utility');
		$Folder = new Folder($folder);
		$files = $Folder->read(true, true);
		$file = array_shift($files[1]);

		if ($file) {
			$result = array();

			$result['info'] = getimagesize($folder . $file);

			App::uses('ExifLib', 'Tools.Lib');
			$exifLib = new ExifLib();
			$result['exif_lib'] = $exifLib->read($folder . $file);
		}
		$this->set(compact('folder', 'file', 'result'));
	}

	//TODO: MOVE to setup?

	public function admin_encode_decode() {

		if ($this->Common->isPosted()) {
			$text = $this->request->data['Test']['text'];
			if (!empty($text) && $this->request->data['Test']['type'] == -1) {
				//$this->Commmon->transientFlashMessage('decoded', 'success');
				$this->Common->flashMessage('decoded', 'success');

				//echo utf8_decode($this->request->data['Test']['text']);

				$this->request->data['Test']['text'] = '';
				$textPieces = explode(NL, $text);
				foreach ($textPieces as $tp) {
					$tp = utf8_decode($tp);
					echo $tp . BR;
					if (!empty($tp)) {
						//$this->request->data['Test']['text'] .= $tp.NL;
					}
				}

				//file_put_contents(TMP.'x.txt', $this->request->data['Test']['text']);

			} elseif (!empty($text) && $this->request->data['Test']['type'] == 1) {
				$this->Common->flashMessage('encoded', 'success');
				//$this->Commmon->transientFlashMessage('encoded', 'success');
				$this->request->data['Test']['text'] = utf8_encode($text);
			}
		} else {
			//$x = file_get_contents(TMP.'x.txt');
			//$this->request->data['Test']['text'] = utf8_decode(utf8_decode($x));
		}
	}

	/**
	 * testing
	 */
	public function admin_sound($t = null) {
		$types = array('audio', 'object', 'sound', 'bgsound', 'embed');

		$soundtype = '';
		if (!empty($t)) {
			if (in_array($t, $types)) {
				$soundtype = strtolower($t);
			}
		}

		$this->set(compact('soundtype', 'types'));
	}

	/**
	 * testing
	 */
	public function admin_old_browser_alert_test() {
	}

	/**
	 * Some cookie-test-functions (in combination with session etc)
	 */
	public function admin_cookietest() {
		//$this->helpers = array_merge($this->helpers, array('Cookie'));
		if (!empty($this->request->params['named']['destroy'])) {
			$this->Cookie->delete('Cookietest');
			$this->Session->delete('Cookietest');
			$this->Common->flashMessage('Complete Reset done', 'success');
			return $this->redirect(array('action' => 'cookietest'));
		}
		if (!empty($this->request->params['named']['reset'])) {
			$this->Session->delete('Cookietest');
			$this->Common->flashMessage('Session Reset done', 'success');
			return $this->redirect(array('action' => 'cookietest'));
		}

		if ($this->Common->isPosted()) {
			$value = $this->request->data['Configuration']['value'];

			if (!empty($value) && !empty($this->request->data['Configuration']['save_to_session'])) {
				$this->Session->write('Cookietest.value', $value);
			}
			if (!empty($value) && !empty($this->request->data['Configuration']['save_to_cookie'])) {
				$this->Cookie->write('Cookietest.value', $value, true, 60 * 5);
			}

			$this->Common->flashMessage('Update done', 'success');
			return $this->redirect(array('action' => 'cookietest'));
		}

		$sessionContent = $this->Session->read('Cookietest.value');;
		$cookieContent = $this->Cookie->read('Cookietest.value');
		$this->set(compact('sessionContent', 'cookieContent'));
	}

	public function admin_caching() {
		if (!empty($this->request->params['named']['clear'])) {
			$this->_cleanViewCache('/admin_tests_caching.php$/');
			$this->Common->flashMessage('CacheFile deleted', 'success');
			return $this->redirect(array('action' => 'caching'));
		}

		$this->set('content', 'dfsdfsdfsdfsdf');

		$this->helpers = array_merge($this->helpers, array('Cache'));
		$this->cacheAction = Configure::read('Caching.default');
	}

	public function _cleanViewCache($pattern) {
		$path = APP . 'tmp' . DS . 'cache' . DS . 'views'; //$this->settings['view_cache_path'];
		if ($pattern{0} !== '/') {
			$pattern = '/' . $pattern . '/i';
		}

		$folder = new Folder($path);
		$contents = $folder->read();
		$files = $contents[1];
		foreach ($files as $file) {
			if (!preg_match($pattern, $file)) {
			continue;
			}
			@unlink($path . DS . $file);
		}
	}

	public function admin_test_mail() {
		$email = Configure::read('Config.adminEmail');
		$username = Configure::read('Config.adminEmailname');

		App::import('Vendor', 'phpmailer', array('file' => 'phpmailer/class.phpmailer.php'));
		$mail = new PHPMailer();
		$mail->IsSMTP(); // telling the class to use SMTP
		//$mail->Host       = Configure::write('Mail.smtp_host'); // SMTP server
		$mail->SMTPDebug = 1; // enables SMTP debug information (for testing)
													 // 1 = errors and messages
													 // 2 = messages only
		$mail->SMTPAuth = true; // enable SMTP authentication
		$mail->Host = Configure::read('Mail.smtp_host'); // sets the SMTP server
		$mail->Port = 25; // set the SMTP port for the GMAIL server
		$mail->Username = Configure::read('Mail.smtp_username'); // SMTP account username
		$mail->Password = Configure::read('Mail.smtp_password'); // SMTP account password

		$mail->SetFrom(Configure::read('Config.noReplyEmail'), Configure::read('Config.noReplyEmailname'));

		$mail->AddReplyTo(Configure::read('Config.noReplyEmail'), Configure::read('Config.noReplyEmailname'));

		$body = 'Some Test';
		$mail->Subject = "PHPMailer Test";

		$mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

		$mail->MsgHTML($body);
		$mail->AddAddress($email, $username);

		$res = $mail->Send();
		echo returns($res);
		die();

		App::uses('EmailLib', 'Tools.Lib');
		$this->Email = new EmailLib();
		$this->Email->to($email, $username);
		$this->Email->replyTo(Configure::read('Config.noReplyEmail'), Configure::read('Config.noReplyEmailname'));

		$this->Email->subject('Test');
		$this->Email->template('simple_email');

		$text = 'Ein Test';
		$this->set(compact('text'));

		$res = $this->Email->send();
		echo returns($res);

		die();
	}

	public function admin_security() {

		$hashType = 'sha1'; // default! //Security::hashType;

		$cipherKey = 'uvwxyz';

		$normalText = '12345abc89äöü';
		$cipherText = Security::cipher($normalText, $cipherKey);
		$normalTextAgain = Security::cipher($cipherText, $cipherKey);

		$this->set(compact('hashType', 'cipherKey', 'normalText', 'cipherText', 'normalTextAgain'));
	}

	public function admin_index() {
	}

}
