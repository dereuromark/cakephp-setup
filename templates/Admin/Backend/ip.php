<?php
/**
 * @var \App\View\AppView $this
 * @var string $ipAddress
 * @var string|null $requestClientIp
 * @var string|null $host
 * @var string $serverIp
 * @var string|null $serverHost
 * @var string $serverName
 * @var string $serverPort
 * @var array<string, string> $requestInfo
 * @var array<string, string> $proxyHeaders
 * @var array<string, array<string>> $networkInterfaces
 */
?>
<div class="columns col-md-12">

	<h1>IP Address</h1>

	<h2>Your Address (Client)</h2>
	<table class="table">
		<tr>
			<td>IP (env REMOTE_ADDR)</td>
			<td><?php echo h($ipAddress); ?></td>
		</tr>
		<tr>
			<td>IP (Request::clientIp)</td>
			<td><?php echo h($requestClientIp); ?><?php if ($requestClientIp !== $ipAddress) { ?> <span class="text-warning">(differs!)</span><?php } ?></td>
		</tr>
		<tr>
			<td>Host</td>
			<td><?php echo h($host); ?></td>
		</tr>
	</table>

	<h2>Server Address</h2>
	<table class="table">
		<tr>
			<td>IP</td>
			<td><?php echo h($serverIp); ?></td>
		</tr>
		<tr>
			<td>Host</td>
			<td><?php echo h($serverHost); ?></td>
		</tr>
		<tr>
			<td>Server Name</td>
			<td><?php echo h($serverName); ?></td>
		</tr>
		<tr>
			<td>Port</td>
			<td><?php echo h($serverPort); ?></td>
		</tr>
	</table>

	<?php if ($requestInfo) { ?>
		<h2>Request Info</h2>
		<table class="table">
			<?php foreach ($requestInfo as $key => $value) { ?>
				<tr>
					<td><?php echo h($key); ?></td>
					<td><?php echo h($value); ?></td>
				</tr>
			<?php } ?>
		</table>
	<?php } ?>

	<?php if ($proxyHeaders) { ?>
		<h2>Proxy Headers found</h2>
		<table class="table">
			<tr>
				<th>ENV</th>
				<th>Value defined</th>
			</tr>
			<?php foreach ($proxyHeaders as $key => $value) { ?>
				<tr>
					<td><?php echo h($key); ?></td>
					<td><?= \Cake\Error\Debugger::exportVar($value, 1); ?></td>
				</tr>
			<?php } ?>
		</table>
	<?php } ?>

	<?php if ($networkInterfaces) { ?>
		<h2>Server Network Interfaces</h2>
		<table class="table">
			<tr>
				<th>Interface</th>
				<th>Addresses</th>
			</tr>
			<?php foreach ($networkInterfaces as $name => $addresses) { ?>
				<tr>
					<td><?php echo h($name); ?></td>
					<td><?php echo h(implode(', ', $addresses)); ?></td>
				</tr>
			<?php } ?>
		</table>
	<?php } ?>

</div>
