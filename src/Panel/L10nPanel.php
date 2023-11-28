<?php

namespace Setup\Panel;

use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\Time;
use DebugKit\DebugPanel;

/**
 * A panel to show authentication and authorization data for current request.
 */
class L10nPanel extends DebugPanel {

	use InstanceConfigTrait;

	/**
	 * Defines which plugin this panel is from so the element can be located.
	 *
	 * @var string
	 */
	public string $plugin = 'Setup';

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
	];

	/**
	 * Data collection callback.
	 *
	 * @param \Cake\Event\EventInterface $event The shutdown event.
	 *
	 * @return void
	 */
	public function shutdown(EventInterface $event): void {
	}

	/**
	 * Get the data for this panel
	 *
	 * @return array<string, mixed>
	 */
	public function data(): array {
		$data = [
			'values' => [
				'datetime' => new DateTime(),
				'date' => new Date(),
				'time-noon' => Time::noon(),
				'time-midnight' => Time::midnight(),
			],
		];

		return $this->_data + $data;
	}

	/**
	 * Get the summary data for a panel.
	 *
	 * This data is displayed in the toolbar even when the panel is collapsed.
	 *
	 * @return string
	 */
	public function summary(): string {
		return '';
	}

}
