<?php
/**
 * @var \App\View\AppView $this
 * @var array $caches
 * @var array $data
 */

use Cake\Core\App;
use Cake\I18n\DateTime;

?>

<div class="columns col-md-12">

<h1>Cache Config</h1>

<p>
	<?php echo $this->Html->link(
		'Compare all engines on this host →',
		['action' => 'cacheBenchmark'],
		['class' => 'btn btn-outline-primary btn-sm'],
	); ?>
</p>

<table class="table table-striped">
	<thead>
		<tr>
			<th>Config</th>
			<th>Engine</th>
			<th>Groups</th>
			<th>Duration</th>
			<th>Prefix / Path / Host</th>
			<th>Last test value</th>
			<th>Actions</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($caches as $key => $config) { ?>
			<?php
			$engineClass = $config['className'];
			$engine = App::shortName($engineClass, 'Cache/Engine', 'Engine');
			$groups = isset($config['groups']) && $config['groups'] ? implode(', ', $config['groups']) : '—';

			$location = '—';
			if (isset($config['path'])) {
				$location = $config['path'];
			} elseif (isset($config['host'])) {
				$port = isset($config['port']) ? ':' . $config['port'] : '';
				$location = $config['host'] . $port;
			} elseif (isset($config['prefix'])) {
				$location = $config['prefix'];
			}
			?>
			<tr>
				<td><strong><?php echo h($key); ?></strong></td>
				<td><code><?php echo h($engine); ?></code></td>
				<td><?php echo h($groups); ?></td>
				<td><?php echo h($config['duration'] ?? '—'); ?></td>
				<td><small><?php echo h($location); ?></small></td>
				<td>
					<?php if ($data[$key]) { ?>
						<?php echo h($data[$key]); ?>
						<br>
						<small><?php echo $this->Time->timeAgoInWords(new DateTime($data[$key])); ?></small>
					<?php } else { ?>
						—
					<?php } ?>
				</td>
				<td>
					<?php echo $this->Form->postButton(
						'Store test',
						['?' => ['key' => $key]],
						['class' => 'btn btn-sm btn-primary', 'form' => ['class' => 'd-inline']],
					); ?>
					<details class="d-inline-block ms-2">
						<summary>Details</summary>
						<pre><?php echo h(print_r($config, true)); ?></pre>
					</details>
				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>

</div>
