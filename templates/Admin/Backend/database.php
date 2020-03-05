<?php
/**
 * @var \App\View\AppView $this
 * @var array $dbTables
 * @var int $dbSize
 */
?>
<div class="columns col-md-12">

<h1><?php echo count($dbTables); ?> <?php echo __('DB Tables');?></h1>

<p>Database size: <?php echo $this->Number->toReadableSize($dbSize); ?></p>

<table class="table list">
<tr><th>Name</th><th>Rows</th><th>Size</th><th>Collation</th><th>Updated</th><th>Comment</th></tr>
<?php
foreach ($dbTables as $table) {
	if (preg_match('/phinxlog$/', $table['Name'])) {
		continue;
	}

	// TODO: format updated (red if today, orange if yesterday, yellow if the day beforeYesterday)
	$updated = $table['Update_time'];

	echo '<tr>';
	echo '<td>' . h($table['Name']) . '</td>';
	echo '<td>' . $table['Rows'] . '</td>';
	echo '<td>' . $this->Number->toReadableSize($table['Data_length']) . '</td>';
	echo '<td>' . $table['Engine'] . ' ' . $table['Collation'] . '</td>';
	echo '<td>' . $updated . '</td>';
	echo '<td>' . h($table['Comment']) . '</td>';
	echo '</tr>';
}
?>
</table>

</div>
