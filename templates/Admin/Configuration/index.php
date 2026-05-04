<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $memory
 * @var mixed $serverLoad
 * @var array $uptime
 */
use Cake\Core\Configure;
use Setup\Utility\Debug;
use Setup\Utility\System;
?>

<div class="index col-md-12">

<h2>Configuration</h2>

	<h3>Server info</h3>
	Server-Uptime:
	<?php if (!empty($uptime)) { ?>
		<?php echo $uptime['days'];?> <?php echo __d('setup', 'Days'); ?>, <?php echo $uptime['hours'];?> <?php echo __d('setup', 'Hours'); ?>, <?php echo $uptime['mins'];?> <?php echo __d('setup', 'Minutes'); ?>
	<?php } else { ?>
		<i>n/a (only for unix/linux server)</i>
	<?php } ?>
	<br /><br />
	Load averages for server: <?php echo ($serverLoad); ?><br />
	<?php echo __d('setup', 'Memory'); ?>: <?php echo ($memory); ?>
	<br />
	Current Memory Usage (Single User): <?php echo $this->Number->toReadableSize(Debug::memoryUsage()).' (Peak: '.$this->Number->toReadableSize(Debug::peakMemoryUsage()).')'; ?>

	<h3>Application info</h3>
	CakeVersion: <?php echo Configure::version(); ?>
	<br />
	Debug-Mode: <?php echo $this->element('Setup.ok', ['value' => $this->element('Setup.yes_no', ['value' => (bool)Configure::read('debug')]), 'ok' => !Configure::read('debug'), 'escape' => false]);?>  | Productive: <?php echo $this->element('Setup.ok', ['value' => $this->element('Setup.yes_no', ['value' => (bool)Configure::read('Config.live')]), 'ok' => (bool)Configure::read('Config.live'), 'escape' => false]); ?><br />
	<br />
	Errors: <?php
	$level = error_reporting();
	$errorString = System::error2string($level, true);
	echo $errorString;
	?>

	<h3>Configuration Files</h3>
	<?php
	$files = [
		'config' => 'app_local.php'
	];
	if ($customFiles = Configure::read('App.configFiles')) {
		$files = array_merge($files, $customFiles);
	}
	foreach ($files as $file) {
		echo $this->element('Setup.yes_no', ['value' => file_exists(ROOT . DS . 'config' . DS . $file)]).' '.h($file).' &nbsp; ';
	}
	?>



<br><br>


<?php /*
		<li><?php echo $this->Html->link(__d('setup', 'Disk Space'), ['action'=>'disk_space']); ?></li>
		<li><?php echo $this->Html->link(__d('setup', 'Environment & Database'), ['action'=>'environment']); ?></li>
		<li><?php echo $this->Html->link(__d('setup', 'Setup (Folder and Rights)'), ['action'=>'setup']); ?></li>
		<li><?php echo $this->Html->link(__d('setup', 'Check Mail'), ['action'=>'check_mail']); ?></li>
		<li><?php echo $this->Html->link(__d('setup', 'Configuration Parameters (Status)'), ['action'=>'status']); ?></li>
		<li><?php echo $this->Html->link(__d('setup', 'All Set Constants'), ['action'=>'constants']); ?></li>
		<li><?php echo $this->Html->link(__d('setup', 'Superglobals (GET, POST, SESSION etc)'), ['action'=>'superglobals']); ?></li>

		<li><?php echo $this->Html->link(__d('setup', 'Session Infos (Duration)'), ['action'=>'session']); ?></li>
		<li><?php echo $this->Html->link(__d('setup', 'Clear Session (Interactive)'), ['action'=>'clearsession']); ?></li>
		<li><?php echo $this->Html->link(__d('setup', 'Clear Cookies (Interactive)'), ['action'=>'clearcookies']); ?></li>

		<li><?php echo $this->Html->link(__d('setup', 'Sql Dump, Backup, Restore'), ['action'=>'sql']); ?></li>
		<li><?php echo $this->Html->link(__d('setup', 'Cache'), ['action'=>'cache']); ?></li>
		<li><?php echo $this->Html->link(__d('setup', 'Clear Cache (With full output)'), ['action'=>'clearcache']); ?></li>

		<li><?php echo $this->Html->link(__d('setup', 'Log Files'), ['action'=>'logs']); ?></li>
		<li><?php echo $this->Html->link(__d('setup', 'Server Log Files'), ['action'=>'serverlogs']); ?></li>
*/ ?>

<div class="actions">
<ul>

</ul>
</div>

</div>
