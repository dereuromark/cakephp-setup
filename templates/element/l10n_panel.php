<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $values
 */

if (!isset($values)) {
	$values = [];
}

?>

<section class="section-tile">
	<h1>Localization</h1>

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

	<h2>Translations</h2>
	<?php
	$translator = \Cake\I18n\I18n::getTranslator();
	$messages = $translator->getPackage()->getMessages();
	?>
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
