<h2>Icons</h2>
<h3>Active</h3>
<?php
	$missing = array();
	$matched = array();

	foreach ($icons as $const => $icon) {
		if (!empty($icon) && file_exists(IMAGES . 'icons' . DS . $icon)) {
			echo $this->Html->image('icons/' . $icon, array('title' => $const)) . ' ';
			$matched[$const] = $icon;
		} else {
			$missing[] = $const . ' (' . h($icon) . ')';
		}
	}
?>

<h3>Missing</h3>
Icon files are missing (inside /icons folder)

<?php
	echo '<ul>';
	foreach ($missing as $m) {
		echo '<li>';
		echo $m;
		echo '</li>';
	}
	echo '</ul>';

?>

<h3>Unmatched</h3>
Icon files that are useless right now (inside /icons folder)
<br />

...//TODO