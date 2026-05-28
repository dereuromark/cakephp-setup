<?php
/**
 * @var \App\View\AppView $this
 * @var array $dbTables
 * @var int $dbSize
 * @var int $maxSize
 */
?>
<div class="columns col-md-12">

<h1><?php echo count($dbTables); ?> <?php echo __d('setup', 'DB Tables');?></h1>
<p>Excluding phinxlog migration tables</p>
<p>Database size: <?php echo $this->Number->toReadableSize($dbSize); ?></p>

<table class="table list">
<tr><th>Name</th><th>Rows</th><th>Size</th><th>Collation</th><th>Updated</th><th>Comment</th></tr>
<?php

foreach ($dbTables as $table) {
	// TODO: format updated (red if today, orange if yesterday, yellow if the day beforeYesterday)
	$updated = $table['Update_time'];
	$length = $table['Data_length'] ?? 0;
	$size = $maxSize ? $length / $maxSize : 0;
	$percent = max(0, min(100, (int)round($size * 100)));

	echo '<tr>';
	echo '<td>' . h($table['Name']) . '</td>';
	echo '<td>' . $table['Rows'] . '</td>';
	echo '<td>' . $this->Number->toReadableSize($length);
	echo '<div class="progress mt-1" style="max-width: 180px;">';
	echo '<div class="progress-bar" role="progressbar" style="width: ' . $percent . '%;" aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100"></div>';
	echo '</div>';
	echo '</td>';
	echo '<td>' . $table['Engine'] . ' ' . $table['Collation'] . '</td>';
	echo '<td>' . $updated . '</td>';
	echo '<td>' . h($table['Comment']) . '</td>';
	echo '</tr>';
}
?>
</table>

</div>
