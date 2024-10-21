<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $sessionConfig
 * @var array $sessionData
 * @var mixed $time
 */
?>
<div class="col-md-12">

<h2>Session</h2>

Time: <?php echo $this->Time->nice($time); ?>


<br />
<h3>Session Config</h3>
<pre>
<?php
echo print_r($sessionConfig);
?>
</pre>

<h3>Own Session Value</h3>

	<p>ID: <code><?php echo h($sessionData['id']); ?></code></p>
	<?php if (!empty($sessionData['expires'])) { ?>
		<p>Expires: <?php echo $this->Time->nice($sessionData['expires']); ?></p>
	<?php } ?>
	<?php if (!empty($sessionData['data'])) { ?>
		<p>Data: <?php echo h($sessionData['data']); ?></p>
	<?php } ?>


<h3>Server Timeout</h3>
<?php
$currentTimeoutInSecs = (int)ini_get('session.gc_maxlifetime');

echo $currentTimeoutInSecs . ' sec = ' . $this->Time->timeAgoInWords(time() + $currentTimeoutInSecs, []);

?>

<br />
<h3>Garbage Collector Settings</h3>
<?php
$currentProbability = ini_get('session.gc_probability');
$currentDivisor = ini_get('session.gc_divisor');


echo 'Probability: '. $currentProbability . ' - Divisor: ' . $currentDivisor;
?>
<br /><br />

<h3>Testing Setting</h3>
<?php

# test setting
ini_set('session.gc_maxlifetime', 111111);
$currentTimeoutInSecs = ini_get('session.gc_maxlifetime');
echo $currentTimeoutInSecs . ' sec = ' . $this->Time->timeAgoInWords(time() + $currentTimeoutInSecs, []);


?>

</div>
