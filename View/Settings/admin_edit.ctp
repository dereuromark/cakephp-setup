<div class="span-21 last form">
<h2><?php printf(__('Add %s'), __('Setting')); ?></h2>

<?php echo $this->Form->create('Setting');?>
	<fieldset>
		<legend><?php printf(__('Add %s'), __('Setting')); ?></legend>
	<?php
		echo $this->Form->input('key');
		echo $this->Form->input('value');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>

<br /><br />

<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(sprintf(__('List %s'), __('Settings')), array('action' => 'index'));?></li>
	</ul>
</div>