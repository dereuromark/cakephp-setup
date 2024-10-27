
<?php
/**
 * @var \App\View\AppView $this
 * @var string $ipAddress
 * @var string|null $host
 */
?>
<div class="columns col-md-12">

	<h1>IP Address</h1>

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

</div>
