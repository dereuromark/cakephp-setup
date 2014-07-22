
<div class="page index">
<h2><?php echo __('Sql Logs');?></h2>
<?php echo count($sqlContent); ?> Sql-Files verfügbar
<?php if (!empty($sqlContent)) { ?>

<?php } ?>

<?php
App::import('Helper', 'Number');
$n = new NumberHelper(new View(null));
foreach ($sqlContent as $array) {

echo '<h3>' . $this->Datetime->niceDate($array['time'], FORMAT_NICE_YMDHMS) . ': ' . h($array['header']) . '</h3>';
echo h($array['location']);

if (!empty($array['data'])) {
	echo '<table class="list">';
	$header = array_shift($array['data']);
	echo '<tr>';
	foreach ($header as $h) {
		echo '<th>' . h($h) . '</th>';
	}
	echo '</tr>';
	foreach ($array['data'] as $row) {
		echo '<tr>';
		foreach ($row as $nr => $field) {
			if (false && $nr == 1) {
				// sql syntax highlighting
				$field = $this->Geshi->parse($field, 'sql');
			} else {
				$field = h($field);
			}
			echo '<td>' . $field . '</td>';
		}
		echo '</tr>';
	}

	echo '</table>';
}

}



?>

</div>