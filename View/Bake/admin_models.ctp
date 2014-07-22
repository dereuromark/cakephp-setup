<div class="page form">
<h2>Bake Models</h2>
APP Tables: <?php echo $stats['app']; ?> of <?php echo $stats['all']; ?> total

<h3>Available</h3>
<ul>
<?php
foreach ($tables as $table) {
	echo '<li>';
	echo $this->Html->link(Inflector::camelize(Inflector::singularize($table)), array('action' => 'models', $table)) . ' (' . h($table) . ')';
	echo '</li>';
}
?>
</ul>

</div>
<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('Back'), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(__('Bake Tables'), array('action' => 'tables'));?></li>
	</ul>
</div>