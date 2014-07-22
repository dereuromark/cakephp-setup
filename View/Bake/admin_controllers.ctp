<div class="page form">
<h2>Bake Controllers</h2>

<h3>Available</h3>
<ul>
<?php
foreach ($models as $modelName => $model) {
	echo '<li>';
	echo $this->Html->link($modelName, array('action' => 'controllers', $modelName)) . ' (' . h($model) . ')';
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