<h2>Environment & Database</h2>
here is the <?php echo $this->Html->link('Full PHP INFO', array('action' => 'phpinfo'));?>

<h3>Infos</h3>
<table class="list">
<tr>
<td>OS</td>
<td><?php echo h(PHP_OS);?> (<?php echo h(@php_uname());?>)</td>
</tr><tr>
<td>Windows</td>
<td><?php echo $this->Format->yesNo(WINDOWS);?></td>
</tr>
</table>

<br />
<h3>Tests</h3>
<table class="list">
<?php


foreach ($serverinfo as $x => $info) {
	$okIcon = '';
	if (!empty($info['ok'])) {
		$ok = (int)$info['ok'];

		if ($ok == 1) {
			$okIcon = $this->Format->icon('warning');
		} elseif ($ok == 2 || $ok == -1) {
			$okIcon = $this->Format->yesNo($ok, 'OK', 'NOT OK', 2);
		} else {
			$okIcon = '?';
		}
	}

	echo '<tr>';
	echo '<td>' . $okIcon . '</td>';
	echo '<td>' . $x . '</td>';
	echo '<td>' . $info['value'] . '</td>';
	echo '<td><small>' . (!empty($info['descr']) ? $info['descr'] : '') . '</small></td>';
	echo '</tr>';
}
?>
</table>

<?php

?>