
<?php
/**
 * @var \App\View\AppView $this
 * @var string $ipAddress
 * @var string|null $host
 * @var array<string, string> $proxyHeaders
 */
?>
<div class="columns col-md-12">

	<h1>IP Address</h1>

	<h2>Your Address</h2>
	<table class="table">
		<tr>
			<td>
				IP
			</td>
			<td>
				<?php echo h($ipAddress); ?>
			</td>
		</tr>
		<tr>
			<td>
				Host
			</td>
			<td>
				<?php echo h($host); ?>
			</td>
		</tr>
	</table>

	<?php if ($proxyHeaders) { ?>
		<h2>Proxy Headers found</h2>
		<table class="table" width="">
			<tr>
				<th>ENV</th><th>Value defined</th>
			</tr>
			<?php foreach ($proxyHeaders as $key => $value) { ?>
				<tr>
					<td>
						<?php echo h($key); ?>
					</td>
					<td>
						<?= \Cake\Error\Debugger::exportVar($value, 1); ?>
					</td>
				</tr>
			<?php } ?>
		</table>
	<?php } ?>

</div>
