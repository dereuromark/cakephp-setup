<h2>Installer</h2>

<h3>Preparations</h3>
<ul>
<li>It is adviced to at least activate the admin prefix in your `core.php`:
	<pre>Configure::write('Routing.prefixes', array('admin'));</pre>
</li>
</ul>

<h3>Begin</h3>
<?php echo $this->Html->link(__('Continue to step %s', 1), array('action' => 'step1')); ?>