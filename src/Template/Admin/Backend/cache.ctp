<?php
/**
 * @var \App\View\AppView $this
 */

use Cake\Error\Debugger;
use Cake\I18n\FrozenTime;

?>

<div class="columns col-md-12">

<h1>Cache Config</h1>

<div class="actions">
<ul>
	<?php foreach ($caches as $key => $config) { ?>
	<li>
		<h2><?php echo h($key); ?></h2>

		<div>
			Data: <?php echo Debugger::exportVar($data[$key]); ?>
			<?php if ($data[$key]) { ?>
				<small>(<?php echo $this->Time->timeAgoInWords(new FrozenTime($data[$key])); ?>)</small>
			<?php } ?>
			<div>
			<?php echo $this->Form->postLink('Store current time for testing', ['?' => ['key' => $key]], ['class' => 'button primary btn btn-primary']); ?>
			</div>
		</div>

		<details>
			<summary>Details</summary>
		<pre><?php echo print_r($config, true); ?></pre>
		</details>
	</li>
	<?php } ?>
</ul>
</div>

</div>
