<?php

$serverinfo = array(
	'normal' => date('Y-m-d H:i:s', time()),
);
?>

<div class="page view">
<h2><?php echo __('Time');?></h2>
On a productive system this helps to check weather everything is still the way it should be

<h3>Timezones</h3>
<?php
date_default_timezone_set('Africa/Tunis');

/*
$dateTime = new DateTime($this->Time->fromString($serverinfo['normal']));
$DateTimeZone = $this->Datetime->getTimezone();
?>
<?php echo timezone_name_get($DateTimeZone)?>
*/
?>
<br />
<br />
Aktuelle Zeitzone: <?php echo date_default_timezone_get();?>

<h3>Normal Server Time (Server in Germany)</h3>
<ul>
<li><?php echo $serverinfo['normal']?></li>
</ul>

<h3>Your Time (as automatically selected - or manually specified)</h3>
<ul>
<li><?php echo date('Y-m-d H:i:s', $this->Time->fromString($serverinfo['normal'], -2));?> (-2)</li>
<li><?php echo date('Y-m-d H:i:s', $this->Time->fromString($serverinfo['normal'], 2));?> (+2)</li>
</ul>
Careful:<br />
NOW You need to add DaylightSavings each time you use a manual time (otherwise it will be off - and in the winter 1 hour too early!)



</div>