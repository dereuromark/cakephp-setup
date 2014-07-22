

<div class="page index">
<h2><?php echo __('Locale');?></h2>

<h3>Test Locale</h3>
<?php echo $this->Form->create('Configuration');?>
	<fieldset>
		<legend><?php echo __('Chose desired result'); ?></legend>
	<?php
		echo $this->Form->input('Form.format');

		echo $this->Form->input('Form.locale', array('placeholder' => __('e.g. de_DE.utf8')));
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

<?php echo $this->Form->end(__('Submit'));?>

<h3>System Locales</h3>
<?php echo pre($systemLocales);?>

<h3>Tryouts</h3>
<ul>
<?php
	foreach ($localeSettings as $key => $settings) {
		echo '<li>';
		echo '<b>' . $key . '</b>' . BR . $settings['res'];
		echo pre($settings);
		echo BR . BR . '</li>';
	}
?>
</ul>
</div>