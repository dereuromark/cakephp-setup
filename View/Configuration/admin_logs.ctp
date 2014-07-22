<div class="page index">
<h2><?php echo __('Logs');?></h2>
<div style="margin-bottom: 10px;" class="logfileList">
<?php
	foreach ($logFiles as $logFile) {
		//$size = filesize($logFile);
		if (!file_exists(LOGS . $logFile . '.log')) {
			continue;
		}
		$logFileName = extractPathInfo('file', $logFile);
		$logFile = LOGS . $logFile . '.log';
		echo '<span>' . $this->Html->link($logFileName, array('action' => 'log', $logFileName)) . ' <small>(' . $this->Numeric->toReadableSize(filesize($logFile)) . ')</small></span> ';
	}
?>
</div>

<?php
echo $this->element('Setup.Configuration/logs');
?>

<div class="actions">
	<ul>
		<li><?php echo $this->Form->postLink(__('Reset all'), array('?' => array('empty' => 'all')), array(), __('Sure?')); ?></li>
	</ul>
</div>

</div>