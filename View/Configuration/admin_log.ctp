<?php
	$logFile = $logFileContent;
	$logFile = array_shift($logFile);
	$logFile = LOGS . $logFile['file'];
	$size = filesize($logFile);
?>
<div class="page index">
<h2><?php echo __('Log');?></h2>
<?php echo __('Size');?>: <?php echo $this->Format->warning($this->Numeric->toReadableSize($size), $size < 8 * 1024 * 1024); ?>

<?php
echo $this->element('Setup.Configuration/logs', array('details' => true));
?>



<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('Back'), array('action' => 'logs')); ?></li>
	</ul>
</div>

</div>