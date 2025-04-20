<?php
/**
 * @var \App\View\AppView $this
 * @var bool $isMaintenanceModeEnabled
 * @var bool $whitelisted
 * @var array<string> $whitelist
 * @var string $ip
 */
?>
<div class="columns col-md-12">

<h1>Setup Backend Tools</h1>

	<h2>Maintenance Mode</h2>
	<p>From here you can put your application into maintenance mode if needed.</p>

	<h3>Current Status</h3>
	<ul>
		<li><b><?php echo $isMaintenanceModeEnabled ? 'Enabled' : 'Disabled'; ?></b></li>
		<li>Your IP: <?php echo h($ip); ?></li>
		<li>You are currently <?php echo $whitelisted ? 'whitelisted' : 'NOT whitelisted'; ?></li>
	</ul>

	<p>
		<?php
		if (!$isMaintenanceModeEnabled) {
			echo $this->Form->postLink('Go to Maintenance mode', ['action' => 'maintenance', '?' => ['maintenance' => 1]], ['class' => 'btn btn-danger']);
		} else {
			echo $this->Form->postLink('Leave Maintenance mode', ['action' => 'maintenance', '?' => ['maintenance' => 0]], ['class' => 'btn btn-warning']);
		}
		?>

	</p>
	<p>Your IP will automatically be whitelisted. So you can still browse.</p>


	<h2>Whitelist</h2>
	<ul>
		<?php foreach ($whitelist as $ip) { ?>
		<li><?php echo h($ip); ?></li>
		<?php } ?>
	</ul>


</div>
