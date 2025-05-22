<?php
/**
 * @var \App\View\AppView $this
 * @var array $caches
 * @var array $data
 */

use Cake\Core\App;
use Cake\Error\Debugger;
use Cake\I18n\DateTime;
?>

<div class="columns col-md-12">

<h1>Cache Config</h1>

<div class="actions">
<ul>
	<?php foreach ($caches as $key => $config) { ?>
		<?php
		$engineClass = $config['className'];
		$engine = App::shortName($engineClass, 'Cache/Engine', 'Engine');
		?>
	<li>
		<h2>[<?php echo h($engine); ?>] <?php echo h($key); ?></h2>

		<div>
			Data: <?php echo Debugger::exportVar($data[$key]); ?>
			<?php if ($data[$key]) { ?>
				<small>(<?php echo $this->Time->timeAgoInWords(new DateTime($data[$key])); ?>)</small>
			<?php } ?>
			<div>
			<?php echo $this->Form->postLink('Store current time for testing', ['?' => ['key' => $key]], ['class' => 'button primary btn btn-primary']); ?>
			</div>
		</div>

		<details style="margin-bottom: 12px">
			<summary>Details</summary>
		<pre><?php echo print_r($config, true); ?></pre>
		</details>
	</li>
	<?php } ?>
</ul>
</div>

</div>
