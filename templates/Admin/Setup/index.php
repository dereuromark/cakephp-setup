<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="columns col-md-12">

<h1>Setup Backend Tools</h1>


	<ul>
		<li><?php echo $this->Html->link('Configuration', ['controller' => 'Configuration', 'action' => 'index']);?></li>
		<li><?php echo $this->Html->link('PHP Info', ['controller' => 'Backend', 'action' => 'phpinfo']);?></li>
		<li><?php echo $this->Html->link('Session Info', ['controller' => 'Backend', 'action' => 'session']);?></li>
		<li><?php echo $this->Html->link('Cache Info and Testing', ['controller' => 'Backend', 'action' => 'cache']);?></li>
		<li><?php echo $this->Html->link('Database Info', ['controller' => 'Backend', 'action' => 'database']);?></li>
		<li><?php echo $this->Html->link('ORM Type Map', ['controller' => 'Backend', 'action' => 'typeMap']); ?></li>
	</ul>

</div>
