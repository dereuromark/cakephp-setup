<h2><?php echo __('DB Tables');?></h2>

<?php echo $this->Form->create('Configuration');?>
	<fieldset>
		<legend><?php echo __('Tables');?></legend>

<table class="list">
<tr><th>&nbsp;</th><th>Name</th><th>Rows</th><th>Collation</th><th>Updated</th><th>Comment</th></tr>
<?php
foreach ($dbTables as $table) {
	// TODO: format updated (red if today, orange if yesterday, yellow if the day beforeYesterday)
	$updated = $table['TABLES']['Update_time'];
	//$tableName = mb_substr($table['TABLES']['Name'], mb_strlen($tablePrefix));

	echo '<tr>';
	echo '<td>' . $this->Form->input('Form.' . $table['TABLES']['Name'], array('label' => false, 'type' => 'checkbox', 'div' => false)) . '</td>';
	echo '<td>' . h($table['TABLES']['Name']) . '</td>';
	echo '<td>' . $table['TABLES']['Rows'] . '</td>';
	echo '<td>' . $table['TABLES']['Collation'] . '</td>';
	echo '<td>' . $updated . '</td>';
	echo '<td>' . $table['TABLES']['Comment'] . '</td>';
	echo '</tr>';
}
?>
</table>


	</fieldset>

<?php echo $this->Form->end(__('Submit'));?>