<?php
App::uses('Folder', 'Utility');
App::uses('AppShell', 'Console/Command');

/**
 * A shell to to help automate PO validation
 *
 * Run it as `cake Setup.Translations`.
 *
 * @copyright Mark Scherer
 * @license MIT
 */
class TranslationsShell extends AppShell {

	public $tasks = array('DbConfig', 'Extract');

	/**
	 * Asserts all languages in the Locale folder.
	 *
	 * @return void
	 */
	public function assert() {
		if (!empty($this->params['plugin'])) {
			$plugin = Inflector::camelize($this->params['plugin']);
			if (!CakePlugin::loaded($plugin)) {
				CakePlugin::load($plugin);
			}
			$this->_paths = array(CakePlugin::path($plugin));
			$this->params['plugin'] = $plugin;
		} else {
			$this->_paths = [APP];
		}

		foreach ($this->_paths as $path) {
			$rootFolder = $path . 'Locale' . DS;
			$languages = (new Folder($rootFolder))->read();
			$languages = array_values(preg_grep('/^' . '[a-z]+' . '$/i', $languages[0]));

			$errors = 0;
			foreach ($languages as $language) {
				$localeFolder = $rootFolder . $language . DS . 'LC_MESSAGES' . DS;
				if (!is_dir($localeFolder)) {
					continue;
				}
				$localeFiles = (new Folder($localeFolder))->find('.+\.po');
				foreach ($localeFiles as $localeFile) {
					if (!file_exists($localeFolder . $localeFile)) {
						continue;
					}
					$this->out('Checking ' . $language . ':' . $localeFile, 1, Shell::VERBOSE);
					$catalog = I18n::loadPo($localeFolder . $localeFile);
					foreach ($catalog as $original => $translation) {
						if ($original === '') {
							continue;
						}
						// 2.6 changes regarding comment
						if (is_array($translation)) {
							$translation = array_shift($translation);
						}
						if ($translation === '') {
							continue;
						}

						// Check for invalid %
						if (!empty($this->params['strict'])) {
							$countInvalid = preg_match_all('/(?<!\%)\%(?!s|d|\%)/', $original, $matches);
							$countInvalid2 = preg_match_all('/(?<!\%)\%(?!s|d|\%)/', $translation, $matches);
							if ($countInvalid || $countInvalid2) {
								$msg = sprintf('Invalid %% (needs to be escaped twice) in %s', $language . ':' . $localeFile);
								$errors++;
								$this->err($msg);
								$this->err(' - ' . $original);
								$this->err(' - ' . $translation);
							}
						}

						// Check %s
						//$countOriginal = preg_match_all('/\%s/', $original, $matches);
						$countOriginal = substr_count($original, '%s');
						$countTranslation = substr_count($translation, '%s');

						if (
							!empty($this->params['strict']) && $countOriginal !== $countTranslation
							|| $countOriginal < $countTranslation
						) {
							$msg = sprintf('Expected %s, found %s %%s in translation for %s', $countOriginal, $countTranslation, $language . ':' . $localeFile);
							$errors++;
							$this->err($msg);
							$this->err(' - ' . $original);
							$this->err(' - ' . $translation);
						}

						// Check %d
						//$countOriginal = preg_match_all('/\%s/', $original, $matches);
						$countOriginal = substr_count($original, '%d');
						$countTranslation = substr_count($translation, '%d');

						if (
							!empty($this->params['strict']) && $countOriginal !== $countTranslation
							|| $countOriginal < $countTranslation
						) {
							$msg = sprintf('Expected %s, found %s %%d in translation for %s', $countOriginal, $countTranslation, $language . ':' . $localeFile);
							$errors++;
							$this->err($msg);
							$this->err(' - ' . $original);
							$this->err(' - ' . $translation);
						}
					}
				}
			}

			$this->out('Done, ' . $errors . ' errors.');
		}
	}

	public function getOptionParser() {
		$subcommandParser = [
			'options' => [
				'plugin' => [
					'short' => 'p',
					'help' => 'The plugin to assert.',
					'default' => ''
				],
				'strict' => [
					'short' => 's',
					'boolean' => true,
					'help' => 'Less placeholders and unescaped % is not allowed.'
				],
			]
		];

		return parent::getOptionParser()
			->description("A shell to help automate PO file validation")
			->addSubcommand('assert', [
				'help' =>'Assert PO files',
				'parser' => $subcommandParser
			]);
	}

}
