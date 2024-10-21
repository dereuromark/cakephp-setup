<?php
/**
 * @var \App\View\AppView $this
 * @var int $uploadLimit
 * @var int $postLimit
 * @var int $memoryLimit
 */
?>

<div class="index col-md-12">

<h2>Configuration</h2>

	<h3>System info</h3>
	<table class="table list">
		<tr>
			<td>Upload-Limit</td>
			<td><?php echo $this->Number->toReadableSize($uploadLimit); ?></td>
		</tr>
		<tr>
			<td>Post-Limit</td>
			<td><?php echo $this->Number->toReadableSize($postLimit); ?></td>
		</tr>
		<tr>
			<td>Memory Limit</td>
			<td><?php echo $this->Number->toReadableSize($memoryLimit); ?></td>
		</tr>
	</table>

<br><br>

</div>
