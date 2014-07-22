<h2>Global Vars</h2>
<?php
	foreach ($globalVars as $key => $var) {
		echo '<h3>' . ucfirst($key) . '</h3>';
		echo pre(h($var));
	}
?>
<br />