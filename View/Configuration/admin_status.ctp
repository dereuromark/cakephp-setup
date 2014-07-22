<h2><?php echo __('Configuration');?></h2>


<?php
if (Configure::read('debug') > 0):
	Debugger::checkSecurityKeys();
endif;
?>
<div class="flashMessages">

<?php
	if (is_writable(TMP)):
		echo '<div class="message success">';
			__('Your tmp directory is writable.');
		echo '</div>';
	else:
		echo '<div class="message error">';
			__('Your tmp directory is NOT writable.');
		echo '</div>';
	endif;
?>


<?php
	$settings = Cache::settings();
	if (!empty($settings)):
		echo '<div class="message success">';
				echo __('The %s is being used for caching. To change the config edit APP/config/core.php ', '<em>'. $settings['engine'] . 'Engine</em>');
		echo '</div>';
	else:
		echo '<div class="message error">';
				__('Your cache is NOT working. Please check the settings in APP/config/core.php');
		echo '</div>';
	endif;
?>


<?php
	$filePresent = null;
	if (file_exists(APP . 'Config' . DS . 'database.php')):
		echo '<div class="message success">';
			__('Your database configuration file is present.');
			$filePresent = true;
		echo '</div>';
	else:
		echo '<div class="message error">';
			__('Your database configuration file is NOT present.');
			echo '<br/>';
			__('Rename config/database.php.default to config/database.php');
		echo '</div>';
	endif;
?>

<?php
if (!empty($filePresent)):
	//uses('model' . DS . 'connection_manager');
	App::uses('ConnectionManager', 'Model');
	$db = ConnectionManager::getDataSource('default');
?>

<?php
	if ($db->isConnected()):
		echo '<div class="message success">';
			__('Cake is able to connect to the database.');
		echo '</div>';
	else:
		echo '<div class="message error">';
			__('Cake is NOT able to connect to the database.');
		echo '</div>';
	endif;
?>

<?php endif;?>


<?php
	if ($active_config):
		echo '<div class="message success">';
			__('You have an active configuration file present<br>See: '.$this->Html->link(__('Current Configuration'), array('action'=>'active')));
		echo '</div>';
	else:
		echo '<div class="message error">';
			__('There is NO active configuration file!<br>'.$this->Html->link(__('New Configuration'), array('action'=>'add')));
		echo '</div>';
	endif;
?>

</div>

<br />



<p>
App:
<ul><?php
echo pre(Configure::read('App'));
?>
</ul>
</p>
<br />

<p>
Security:
<ul><?php
echo pre(Configure::read('Security'));
?>
</ul>
</p>
<br />

<p>
Session:
<ul><?php
echo pre(Configure::read('Session'));
?>
</ul>
</p>
<br />

<p>
Cache:
<ul><?php
echo pre(Configure::read('Cache'));
?>
</ul>
</p>
<br />

<p>
Routing:
<ul><?php
echo pre(Configure::read('Routing'));
?>
</ul>
</p>
<br />

<p>
Settings:
<ul><?php
echo pre(Configure::read('Settings'));
?>
</ul>
</p>
<br />