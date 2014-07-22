<h2><?php echo __('Clear Session');?></h2>
<h3>Complete</h3>
<?php echo $this->Html->link('Clear Session', array('action' => 'clearsession', 'reset' => 1), array(), 'Sicher?');?>
<br/>Note: You will be logged out!

<h3>Partly</h3>
<ul>
<?php
$sessionData = $this->Session->read();

foreach ($sessionData as $name => $data) {
	echo '<li>';
	echo $this->Html->link($name, array('action' => 'clearsession', urlencode($name)));
	echo '<ul>';
	if (!empty($data) && is_array($data)) {
		foreach ($data as $secondName => $secondData) {
			echo '<li>';
			echo $this->Html->link($secondName, array('action' => 'clearsession', urlencode($name . '|' . $secondName)));
			echo '</li>';
		}
	}
	echo '</ul>';
	echo '</li>';
}
?>
</ul>