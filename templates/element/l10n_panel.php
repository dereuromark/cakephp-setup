<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $values
 * @var array<string, string> $timezone
 * @var array<string, string> $currency
 * @var array<string, mixed> $messages
 */
?>

<section class="section-tile">
	<h1>Localization</h1>

	<h2>Timezone</h2>
	<ul>
		<li>Default: <?php echo h($timezone['default']); ?></li>
		<?php if ($timezone['output'] !== null) { ?>
			<li>Output: <?php echo h($timezone['output']); ?></li>
		<?php } ?>
		<li>Current: <?php echo h($timezone['current']); ?></li>
	</ul>

	<h2>Datetime/Date/Time</h2>

	<ul>
		<?php foreach ($values as $name => $value) { ?>
		<li>
			<?php echo h($name); ?>: <?php echo (string)$value; ?> (<?php echo get_class($value)?>)
		</li>
		<?php } ?>
	</ul>

	<h2>Languages/Locales</h2>
	<ul>
		<li>
			App.defaultLocale: <code><?php echo h(\Cake\Core\Configure::read('App.defaultLocale')); ?></code>
		</li>
		<li>
			App.defaultTimezone: <code><?php echo h(\Cake\Core\Configure::read('App.defaultTimezone')); ?></code>
		</li>
		<li>
			date_default_timezone_get(): <code><?php echo h(date_default_timezone_get()); ?></code>
		</li>
		<li>
			ini_get('intl.default_locale'): <code><?php echo h(ini_get('intl.default_locale')); ?></code>
		</li>
		<li>

		</li>
	</ul>

	<h2>Currency</h2>
	<ul>
		<?php foreach ($currency as $key => $value) { ?>
			<li>
				<?php echo h($key); ?>: <?php echo h($value); ?>
			</li>
		<?php } ?>
	</ul>

	<h2>Translations</h2>
	<p><?php echo count($messages);?> translations</p>
	<details>
		<summary>Details</summary>
		<table>
			<?php foreach ($messages as $message => $details) { ?>
				<tr>
					<td><?php echo h($message) ?></td>
					<td><ul><?php
						$context = $details['_context'] ?? [];
						foreach ($context as $key => $translation) {
							echo '<li>' . ($key ? h($key). ': ' : '') . h($translation). '</li>';
						}
						?></ul></td>
					<?php } ?>
				</tr>
		</table>
	</details>

</section>
