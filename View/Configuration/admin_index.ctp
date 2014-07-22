<div class="page index">
<h2><?php echo __('Configurations');?></h2>
<h3>Server info</h3>
Server-Uptime:
<?php if (!empty($uptime)) { ?>
<?php echo $uptime['days'];?> <?php echo __('Days'); ?>, <?php echo $uptime['hours'];?> <?php echo __('Hours'); ?>, <?php echo $uptime['mins'];?> <?php echo __('Minutes'); ?>
<?php } else { ?>
	<i>n/a (only for unix/linux server)</i>
<?php } ?>
<br /><br />
Load averages for server: <?php echo ($serverLoad); ?><br />
<?php echo __('Memory'); ?>: <?php echo ($memory); ?>
<br />
Current Memory Usage (Single User): <?php echo $this->Number->toReadableSize(DebugLib::memoryUsage()).' (Peak: '.$this->Number->toReadableSize(DebugLib::peakMemoryUsage()).')'; ?>

<h3>Application info</h3>
CakeVersion: <?php echo Configure::version(); ?>
<br />
Debug-Mode: <?php echo ''.Configure::read('debug');?>  | Productive: <?php echo $this->Format->yesNo(Configure::read('Config.productive')); ?><br />
<br />
Errors: <?php
$level = error_reporting();
App::uses('SystemLib', 'Setup.Lib');
$errorString = SystemLib::error2string($level);
echo $errorString;
?>

<h3>Configuration Files</h3>
<?php
	$files = array(
		'datebase' => 'database.php',
		//'bootstrap' => 'bootstrap_private.php',
		'config' => 'config_private.php'
	);
	if ($customFiles = Configure::read('App.configFiles')) {
		$files = array_merge($files, $customFiles);
	}
	foreach ($files as $file) {
		echo $this->Format->yesNo(file_exists(APP . 'Config' . DS.$file)).' '.h($file).' &nbsp; ';
	}
?>

<?php if (!empty($configurations)) { ?>
<h3>Current Config</h3>

<table class="list">
<tr>
	<th><?php echo $this->Paginator->sort('admin_email');?></th>
	<th><?php echo $this->Paginator->sort('admin_emailname');?></th>
	<th><?php echo $this->Paginator->sort('page_name');?></th>
	<th><?php echo $this->Paginator->sort('max_loginfail');?></th>
	<th><?php echo $this->Paginator->sort('timeout');?></th>
	<th><?php echo $this->Paginator->sort('active');?></th>
	<th class="actions"><?php echo __('Actions');?></th>
</tr>
<?php
$i = 0;
foreach ($configurations as $configuration):
	$class = null;
	if ($i++ % 2 == 0) {
		$class = ' class="altrow"';
	}
?>
	<tr<?php echo $class;?>>
		<td>
			<?php echo $configuration['Configuration']['admin_email']; ?>
		</td>
		<td>
			<?php echo $configuration['Configuration']['admin_emailname']; ?>
		</td>
		<td>
			<?php echo $configuration['Configuration']['page_name']; ?>
		</td>
		<td>
			<?php echo $configuration['Configuration']['max_loginfail']; ?>
		</td>
		<td>
			<?php echo $configuration['Configuration']['timeout']; ?>
		</td>
		<td>
			<?php echo $this->Format->yesNo($configuration['Configuration']['active']); ?>
		</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View'), array('action'=>'view', $configuration['Configuration']['id'])); ?>
			<?php echo $this->Html->link(__('Edit'), array('action'=>'edit', $configuration['Configuration']['id'])); ?>
			<?php echo $this->Form->postLink(__('Delete'), array('action'=>'delete', $configuration['Configuration']['id']), array('escape'=>false), __('Are you sure you want to delete # %s?', $configuration['Configuration']['id'])); ?>
		</td>
	</tr>
<?php endforeach; ?>
</table>

<?php echo $this->element('Tools.pagination'); ?>

<?php } else {
	echo BR.BR;
} ?>

</div>




<div class="actions">
	<ul>
<?php if (false) { ?>
		<li><?php echo $this->Html->link(__('Add %s', __('Configuration')), array('action'=>'add')); ?></li>
<?php } ?>
		<li><?php echo $this->Html->link(__('See Active Configuration'), array('action'=>'active')); ?></li>

		<li><?php echo $this->Html->link(__('PHP Info (Full)'), array('action'=>'phpinfo')); ?></li>
		<li><?php echo $this->Html->link(__('Disk Space'), array('action'=>'disk_space')); ?></li>
		<li><?php echo $this->Html->link(__('Environment & Database'), array('action'=>'environment')); ?></li>
		<li><?php echo $this->Html->link(__('Setup (Folder and Rights)'), array('action'=>'setup')); ?></li>
		<li><?php echo $this->Html->link(__('Check Mail'), array('action'=>'check_mail')); ?></li>
		<li><?php echo $this->Html->link(__('Configuration Parameters (Status)'), array('action'=>'status')); ?></li>
		<li><?php echo $this->Html->link(__('All Set Constants'), array('action'=>'constants')); ?></li>
		<li><?php echo $this->Html->link(__('Superglobals (GET, POST, SESSION etc)'), array('action'=>'superglobals')); ?></li>

		<li><?php echo $this->Html->link(__('Session Infos (Duration)'), array('action'=>'session')); ?></li>
		<li><?php echo $this->Html->link(__('Clear Session (Interactive)'), array('action'=>'clearsession')); ?></li>
		<li><?php echo $this->Html->link(__('Clear Cookies (Interactive)'), array('action'=>'clearcookies')); ?></li>

		<li><?php echo $this->Html->link(__('Sql Dump, Backup, Restore'), array('action'=>'sql')); ?></li>
		<li><?php echo $this->Html->link(__('Cache'), array('action'=>'cache')); ?></li>
		<li><?php echo $this->Html->link(__('Clear Cache (With full output)'), array('action'=>'clearcache')); ?></li>

		<li><?php echo $this->Html->link(__('Log Files'), array('action'=>'logs')); ?></li>
		<li><?php echo $this->Html->link(__('Server Log Files'), array('action'=>'serverlogs')); ?></li>

	</ul>
</div>


	<?php //echo $this->element('setup/admin_index')?>

	<?php //echo $this->element('tests/admin_index')?>