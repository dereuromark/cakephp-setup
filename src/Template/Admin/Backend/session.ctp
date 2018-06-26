<?php
/**
 * @var \App\View\AppView $this
 */

use Cake\I18n\Time;

?>
<div class="col-md-12">

<h2>Session</h2>

Time: <?php echo $this->Time->niceDate($time); ?>


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
		<p>Expires: <?php echo $this->Time->niceDate($sessionData['expires']); ?></p>
	<?php } ?>
	<?php if (!empty($sessionData['data'])) { ?>
		<p>Data: <?php echo h($sessionData['data']); ?></p>
	<?php } ?>


<h3>Server Timeout</h3>
<?php
$currentTimeoutInSecs = (int)ini_get('session.gc_maxlifetime');

echo $currentTimeoutInSecs . ' sec = ' . $this->Time->timeAgoInWords(new Time(time() + $currentTimeoutInSecs), []);

?>

<br />
<h3>Garbage Collector Settings</h3>
<?php
$currentProbability = ini_get('session.gc_probability');
$currentDivisor = ini_get('session.gc_divisor');


echo 'Probability: '. $currentProbability . ' - Divisor: ' . $currentDivisor;
?>

</div>
