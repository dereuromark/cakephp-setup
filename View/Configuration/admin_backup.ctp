<h2><?php echo __('Backup');?></h2>

<h3>Current Backup Files</h3>

<?php

	//echo pre($files[1]);
?>



<h3>Backup now!</h3>
<div class="page form">
<?php echo $this->Form->create('Configuration');?>
	<fieldset>
		<legend><?php echo __('Edit %s', __('Configuration'));?></legend>
	<?php
		echo $this->Form->input('tables', array('multiple' => 'multiple', 'options' => $tables));
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>