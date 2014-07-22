<?php
foreach ($logFileContent as $name => $array) {
	$title = $array['file'];
	if (empty($details)) {
		$title = $this->Html->link($array['file'], array('action' => 'log', $array['name']));
	}
	echo '<h3>' . $title . '</h3>';

	if (!empty($array['content'])) {

		$contentArray = explode(NL, trim($array['content']));
		$contentCount = count($contentArray);
		if ($show > $contentCount) {
			$show = $contentCount;
		}
		$array['content'] = nl2br(h($array['content']));
		$array['content'] = preg_replace('/\b(\d{4})-(\d{2})-(\d{2})\s(\d{2})\:(\d{2})\:(\d{2})\b/', '<b>\1-\2-\3 \4:\5:\6</b>', $array['content']);

		$res = array();
		for ($i = $contentCount - 1; $i > 0; $i--) {
			if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})\s(\d{2})\:(\d{2})\:(\d{2})\b/', $contentArray[$i])) {
				continue;
			}
			$res[] = $contentArray[$i];
			if (count($res) >= $show) {
				break;
			}
		}
		$res = array_reverse($res);

		echo '<div>';
		$date = date('d.m.Y', $array['modified']);
		if ((time() - $array['modified']) < DAY) { // < 1 Tag
			$date = '<b>' . $date . '</b>';
		}

		echo '' . __('Modified') . ': ' . $date . ' |
			' . __('Size') . ': ' . $this->Numeric->toReadableSize($array['size']) . ' |
			<span class="toggleBoxLink hand" id="log' . ucfirst($name) . '">' . $this->Format->icon('details') . ' ' . __('showAll') . ' (' . $contentCount . ')</span> |
			<span class="no">' . $this->Html->link(__('emptyLog'), array('?' => array('empty' => $name)), array(), __('You really want to empty this log file?')) . '</span>';
		echo '<div class="hiddenLogs" style="display:none" id="log' . ucfirst($name) . 'Box">' . $array['content'] . '</div>';

		echo '</div>';

		echo '<div class="default"><ul>';

		foreach ($res as $row) {
			//list($header, $main) = $row;
			echo '<li class="logBody">' . h($row) . '</li>';
		}
		echo '</ul></div>';
	} else {
		echo '<div><i>leer</i></div>';
	}

}