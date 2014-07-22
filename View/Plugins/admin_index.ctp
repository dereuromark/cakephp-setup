<h2>Plugins</h2>

<table class="index">
<?php

foreach ($plugins as $plugin) {
	$plugin = Inflector::underscore(Inflector::humanize($plugin));
	?><tr>
		<td><?php echo $this->Html->link(Inflector::humanize($plugin), array('action' => 'view', Inflector::underscore($plugin))) ?></td>
	</tr>
<?php
}
?></table>