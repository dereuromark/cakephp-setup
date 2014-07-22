<h2>Encode/Decode UTF8</h2>

<div class="page form">
<?php echo $this->Form->create('Test');?>
	<fieldset>
		<legend><?php echo __('Transform');?></legend>
	<?php
		echo $this->Form->input('type', array('options' => array('1' => 'encode', '-1' => 'decode'), 'empty' => 'pleaseSelect'));
		echo $this->Form->input('text', array('type' => 'textarea', 'rows' => 30));
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>

<br /><br />

<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('List %s', __('Configurations')), array('action' => 'index'));?></li>
	</ul>
</div>