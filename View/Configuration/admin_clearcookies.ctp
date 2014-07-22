<h2><?php echo __('Clear Cookies');?></h2>
<h3>Complete</h3>
<?php echo $this->Html->link('Clear Cookies', array('action' => 'clearcookies', 'reset' => 1), array(), 'Sicher?');?>

<h3>Partly</h3>
<ul>
<?php

foreach ($cookieData as $name => $data) {
	echo '<li>';
	echo $this->Html->link($name, array('action' => 'clearcookies', urlencode($name)));
	echo '<ul>';
	if (!empty($data) && is_array($data)) {
		foreach ($data as $secondName => $secondData) {
			echo '<li>';
			echo $this->Html->link($secondName, array('action' => 'clearcookies', urlencode($name . '|' . $secondName)));
			echo '</li>';
		}
	}
	echo '</ul>';
	echo '</li>';
}
?>
</ul>


<h3>Write</h3>
<?php echo $this->Form->create('Form');?>
<?php
	echo $this->Form->input('key');
	echo $this->Form->input('content');
?>
<?php echo $this->Form->end(__('Submit'));?>