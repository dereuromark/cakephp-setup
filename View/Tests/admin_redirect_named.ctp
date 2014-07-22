<h2>Redirect test</h2>

<h3>Result</h3>
<?php
	echo pre($named);
?>

<h3>Test</h3>
<div>
<?php echo $this->Form->create('Test');?>
<?php echo $this->Form->input('include_special', array('type' => 'checkbox')); ?>
<?php echo $this->Form->end(__('Redirect me'));?>
</div>