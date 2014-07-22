<h2>Redirect test</h2>

<h3>Result</h3>
<?php
	echo pre($query);
?>

<h3>Test</h3>
<div>
<?php echo $this->Form->create('Test');?>
<?php echo $this->Form->hidden('foo', array('value' => 'bar')); ?>
<?php echo $this->Form->end(__('Redirect me'));?>
</div>

<br />
<br />
<ul>
<li><?php echo $this->Html->link('Named (old)', array('action' => 'redirect_named'))?></li>
</ul>