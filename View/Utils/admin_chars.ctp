<div class="page form">
<?php echo $this->Form->create('Configuration');?>
	<fieldset>
		<legend><?php echo __('Char Information');?></legend>
	<?php
		echo $this->Form->input('Form.content', array('type' => 'textarea', 'label' => __('Text')));
	?>

	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>

<?php if (!empty($result)) { ?>

<br />
<h3>Ergebnis</h3>

<br class="clear"/>
<div class="info">
<ul>
<?php
	echo ($result);
?>

	</ul>
</div>

<?php } ?>