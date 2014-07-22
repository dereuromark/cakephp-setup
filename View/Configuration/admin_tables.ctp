<h2>Tabellen</h2>
Show, Reset

<br />
<h3>Show</h3>
<ul>
<?php
//pr ($tables);
if (!empty($tableCount)) {
	foreach ($tableCount as $table => $count) {
		echo '<li>' . $table . ': ' . $count . '</li>';
	}
	echo '</ul></div><br /><br />';
}
?>
</ul>