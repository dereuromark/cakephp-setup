<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="columns col-md-12">

<h1>Setup Backend Tools</h1>

	<h2>Information</h2>
	<ul>
		<li><?php echo $this->Html->link('Configuration', ['controller' => 'Configuration', 'action' => 'index']);?></li>
		<li><?php echo $this->Html->link('PHP Info (Full)', ['controller' => 'Backend', 'action' => 'phpinfo']);?></li>
		<li><?php echo $this->Html->link('Session Info', ['controller' => 'Backend', 'action' => 'session']);?></li>
		<li><?php echo $this->Html->link('Cookie Info', ['controller' => 'Backend', 'action' => 'cookies']);?></li>
		<li><?php echo $this->Html->link('Cache Info and Testing', ['controller' => 'Backend', 'action' => 'cache']);?></li>
		<li><?php echo $this->Html->link('ORM Type Map', ['controller' => 'Backend', 'action' => 'typeMap']); ?></li>

		<li><?php echo $this->Html->link(__('System'), ['controller' => 'Backend', 'action' => 'system']); ?></li>
		<li><?php echo $this->Html->link(__('Timezones'), ['controller' => 'Backend', 'action' => 'timezones']);?></li>
		<li><?php echo $this->Html->link(__('Locales'), ['controller' => 'Backend', 'action' => 'locales']);?></li>
		<li><?php echo $this->Html->link('Database Info', ['controller' => 'Backend', 'action' => 'database']);?></li>
		<li><?php echo $this->Html->link(__('Disk Space'), ['controller' => 'Backend', 'action' => 'diskSpace']);?></li>
		<li><?php echo $this->Html->link(__('ENV Config'), ['controller' => 'Backend', 'action' => 'env']);?></li>
	</ul>


	<h2>Maintenance</h2>
	<p>
		<?php
		echo $this->Html->link('Maintenance Mode', ['action' => 'maintenance']);
		?>
	</p>

	<h2>Healthcheck</h2>

	<p>
	<?php
	echo $this->Html->link('Healthcheck', ['controller' => 'Uptime', 'action' => 'index']);
	?>
	</p>
	<p>You can customize this route on project level and add this to your healthcheck (ping) services.</p>

</div>
