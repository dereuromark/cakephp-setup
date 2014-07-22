<h2>Session</h2>

<h3>Settings</h3>
<?php
	echo pre($settings = Configure::read('Session'));

	if (!empty($settings['handler']) && $settings['handler']['engine'] === 'CacheSession' && !empty($settings['handler']['config'])) {
		echo 'Handler-Config:';
		echo pre(Cache::config($settings['handler']['config']));
	}
?>

<h3>Cookie Session Timeout</h3>
<?php
$sessionTimeout = $this->Session->read('Config.time');
echo $this->Datetime->timeAgoInWords($sessionTimeout, array());
echo ' (timestamp: ' . $sessionTimeout . ')';
?>

<br />
<h3>Server Timeout</h3>
<?php
$currentTimeoutInSecs = ini_get('session.gc_maxlifetime');

echo $currentTimeoutInSecs . ' sec = ' . $this->Datetime->timeAgoInWords(time() + $currentTimeoutInSecs, array());
echo BR;
?>

<br />
<h3>Testing Setting</h3>
<?php

# test setting
ini_set('session.gc_maxlifetime', 111111);
$currentTimeoutInSecs = ini_get('session.gc_maxlifetime');
echo $currentTimeoutInSecs . ' sec = ' . $this->Datetime->timeAgoInWords(time() + $currentTimeoutInSecs, array());

?>