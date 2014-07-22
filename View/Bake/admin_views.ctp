<div class="page form">
<h2>Bake Views</h2>

<h3>Available</h3>
<ul>
<?php
foreach ($controllers as $controllerName => $controller) {
	echo '<li>';
	echo $this->Html->link($controllerName, array('action' => 'views', $controllerName)) . ' (' . h($controller) . ')';
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