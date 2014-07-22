<h2>(Flash)Messages Tryout</h2>

<h3>Flash Messages</h3>
The different types of flash messages are outputed above.

<h3>Form (error) messages</h3>
<?php
$this->Form->validationErrors['X']['y'] = 1;
$this->Form->validationErrors['X']['field'] = 1;

$this->Form->create('X');

echo $this->Form->error('X.y', 'Some error without a field');

echo BR . BR;

echo $this->Form->input('X.field');

$this->Form->end();
?>


<h3>Test for color-blindness</h3>
<?php echo $this->Html->link('colorfilter.wickline.org', 'http://colorfilter.wickline.org/'); ?>
<br />
Simple tests for red/green color deficit etc. Make sure your error messages are still visible (and not just gray).
<br /><br />
This page: <?php echo $this->Html->link('Quicklink', 'http://colorfilter.wickline.org/?a=1;r=;l=0;j=1;u=' . $this->Html->url(null, true) . ';t=p'); ?>