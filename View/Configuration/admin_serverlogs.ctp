

<div class="page index">
<h2><?php echo __('Server Logs');?></h2>

<?php
App::import('Helper', 'Number');
$n = new NumberHelper(new View(null));

foreach ($logFileContent as $name => $array) {

	echo '<h3>' . $array['file'] . '</h3>';

if (!empty($array['content'])) {

	$contentArray = explode(NL, trim($array['content']));
	$contentCount = count($contentArray);
	if ($show > $contentCount) {
		$show = $contentCount;
	}

	echo '<div>';
	$date = date('d.m.Y', $array['modified']);
	if ((time() - $array['modified']) < 86400) { // < 1 Tag
		$date = '<b>' . $date . '</b>';
	}
	echo '' . __('Modified') . ': ' . $date . ' |
		' . __('Size') . ': ' . $n->toReadableSize($array['size']) . ' |
		<span class="toggleBoxLink hand" id="log' . ucfirst($name) . '">' . $this->Format->icon('details') . ' ' . __('showAll') . ' (' . $contentCount . ')</span> |
		<span class="no">' . $this->Html->link(__('emptyLog'), array(('?' => array('empty' => $name)), array(), __('You really want to empty this log file?')) . '</span>';
	echo '<div class="hiddenLogs" style="display:none" id="log' . ucfirst($name) . 'Box">' . nl2br(h($array['content'])) . '</div>';

	echo '</div>';

	echo '<div class="default"><ul>';
	for ($i = ($contentCount - $show); $i < $contentCount; $i++) {
		$str = (int)strpos($contentArray[$i], '):'); // first occurance of ): -> linebreak? for better visibility
		if ($str > 0) {
			$header = substr($contentArray[$i], 0, $str + 2);
			$main = substr($contentArray[$i], $str + 2);
		} else {
			$header = $contentArray[$i];
			$main = '';
		}
		echo '<li class="logBody"><div>' . h($header) . '</div>' . h($main) . '</li>';
	}
	echo '</ul></div>';
} else {
	echo '<div><i>leer</i></div>';
}

}

?>

</div>