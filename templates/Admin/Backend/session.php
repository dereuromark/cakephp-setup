<?php
/**
 * @var \App\View\AppView $this
 * @var array $sessionConfig
 * @var \Cake\I18n\DateTime $time
 */

use Cake\I18n\DateTime;

?>
<div class="columns col-md-12">

<h1>Session</h1>

Time: <?php echo $this->Time->niceDate($time); ?>


<br />
<h2>Session Config</h2>
<pre>
<?php
echo print_r($sessionConfig, true);
?>
</pre>

<h2>Own Session Value</h2>

	<p>ID: <code><?php echo h($sessionData['id']); ?></code></p>
	<?php if (!empty($sessionData['expires'])) { ?>
		<p>Expires: <?php echo $this->Time->niceDate($sessionData['expires']); ?></p>
	<?php } ?>
	<?php if (!empty($sessionData['data'])) { ?>
		<p>Data: <?php echo h($sessionData['data']); ?></p>
	<?php } ?>


<h2>Server Timeout</h2>
<?php
$currentTimeoutInSecs = (int)ini_get('session.gc_maxlifetime');

echo $currentTimeoutInSecs . ' sec = ' . $this->Time->timeAgoInWords((new DateTime())->addSeconds($currentTimeoutInSecs), []);

?>

<br />
<h2>Garbage Collector Settings</h2>
<?php
$currentProbability = ini_get('session.gc_probability');
$currentDivisor = ini_get('session.gc_divisor');


echo 'Probability: '. $currentProbability . ' - Divisor: ' . $currentDivisor;
?>

</div>
