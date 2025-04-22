<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $localeSettings
 * @var mixed $result
 * @var mixed $systemLocales
 */
?>
<div class="index col-md-12">

<h2><?php echo __('Locale');?></h2>

<h3>Test Locale</h3>
<?php echo $this->Form->create();?>
	<fieldset>
		<legend><?php echo __('Chose desired result'); ?></legend>
	<?php
		echo $this->Form->control('Form.format');

		echo $this->Form->control('Form.locale', ['placeholder' => __('e.g. de_DE.utf8')]);
	?>
	</fieldset>
<?php if (isset($result)) { ?>
	<fieldset>
		<legend><?php echo __('Result');?></legend>
		<?php
			echo pre($result);
		?>
	</fieldset>
<?php } ?>

<?php echo $this->Form->submit(__('Submit')); echo $this->Form->end();?>

<h3>System Locales</h3>

	<details>
		<summary><?= count($systemLocales); ?> locales</summary>

		<?php echo pre($systemLocales);?>
	</details>
	<br>

<h3>Tryouts</h3>
<ul>
<?php
	foreach ($localeSettings as $key => $settings) {
		echo '<li>';
		echo '<b>' . $key . '</b>' . '<br>' . $settings['res'];
		echo pre($settings);
		echo '<br><br></li>';
	}
?>
</ul>

</div>
