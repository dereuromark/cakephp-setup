<?php
namespace Setup\Routing\Filter;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Routing\DispatcherFilter;
use Cake\View\View;
use Setup\Maintenance\Maintenance;

/**
 * Maintenance Mode filter
 *
 * Example usage:
 *
 *   use Setup\Routing\Filter\MaintenanceFilter;
 *
 *   DispatcherFactory::add(new MaintenanceFilter());
 *
 * You might want to use
 *
 *   if (php_sapi_name() !== 'cli') {}
 *
 * to only add this filter for non CLI requests.
 *
 * @deprecated Since CakePHP 3.3. Use MaintenanceMiddleware instead.
 */
class MaintenanceFilter extends DispatcherFilter {

	/**
	 * Default priority for all methods in this filter
	 *
	 * Per default the priority is 1 (10 is default)
	 * to assert that other dispatchers like Asset are not running first.
	 *
	 * @var int
	 */
	protected $_priority = 1;

	/**
	 * @var string
	 */
	protected $_staticTemplate = 'maintenance';

	/**
	 * Extended default config to be merged with default config.
	 *
	 * @var array
	 */
	protected $_defaultConfigExt = [
		'template' => null,
		'layout' => null,
	];

	/**
	 * Constructor.
	 *
	 * @param array $config Settings for the filter.
	 * @throws \InvalidArgumentException When 'when' conditions are not callable.
	 */
	public function __construct($config = []) {
		$this->_defaultConfig = $this->_defaultConfigExt + $this->_defaultConfig;
		parent::__construct($config);
	}

	/**
	 * MaintenanceMode::beforeDispatch()
	 *
	 * @param \Cake\Event\Event $event
	 * @return \Cake\Http\Response|null
	 */
	public function beforeDispatch(Event $event) {
		/* @var \Cake\Http\ServerRequest $request */
		$request = $event->data['request'];
		$ip = $request->clientIp();
		$Maintenance = new Maintenance();
		if (!$Maintenance->isMaintenanceMode($ip)) {
			return null;
		}

		$body = __d('setup', 'Maintenance work');
		$body = $this->_body();

		$event->data['response']->header('Retry-After', HOUR);
		$event->data['response']->statusCode(503);
		$event->data['response']->body($body);
		$event->stopPropagation();
		return $event->data['response'];
	}

	/**
	 * Find out what body content we need:
	 * - Dynamic rendering
	 * - Static HTML
	 * - Basic (translated) string
	 *
	 * @return string
	 */
	public function _body() {
		$template = (bool)Configure::read('Maintenance.template');
		if ($template) {
			$template = 'maintenance';
			$layout = (bool)Configure::read('Maintenance.layout');
			if ($layout) {
				$layout = 'maintenance';
			} else {
				$layout = null;
			}

			$View = $this->_getView();
			$body = $View->render($template, $layout);
			return $body;
		}

		$template = APP . 'Template' . DS . 'Error' . DS . $this->_staticTemplate;
			if (file_exists($template)) {
				$body = file_get_contents($template);
				return $body;
			}

		$body = __d('setup', 'Maintenance work');
		return $body;
	}

  /**
   * MaintenanceFilter::_getView()
   *
   * @return \Cake\View\View
   */
  protected function _getView() {
		$helpers = (array)Configure::read('Maintenance.helpers');

		$View = new View(null);
		$View->viewVars = (array)Configure::read('Maintenance.viewVars');
		$View->helpers = $helpers;
		$View->loadHelpers();
		$View->hasRendered = false;
		$View->viewPath = 'Maintenance';
		return $View;
  }

}
