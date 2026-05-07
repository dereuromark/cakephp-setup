<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, array{available: bool, className: string, reason?: string}> $availability
 * @var array<string, array<string, array{ms: float, opsPerSec: float}|array{error: string}>>|null $results
 */

use Cake\Core\App;

$unavailable = array_filter($availability, fn (array $entry): bool => !$entry['available']);
?>

<div class="columns col-md-12">

<h1>Cache Benchmark</h1>

<p>
	Compare read/write performance across all CakePHP cache engines available on this host.
	Each engine runs in an isolated throwaway config (prefix <code>setup_benchmark_</code>) — your real cache configs are not touched.
</p>

<p>
	<strong>Method:</strong> for each engine, pre-populate 1000 small (~100B) values into a "read" keyspace,
	then time 1000 reads. Then time 1000 writes into a separate "write" keyspace, so the read measurement is not tainted by warm engine state from a write loop.
</p>

<div class="actions" style="margin: 12px 0">
	<?php echo $this->Form->postButton(
		$results === null ? 'Run benchmark' : 'Run again',
		[],
		['class' => 'button primary btn btn-primary'],
	); ?>
</div>

<?php if ($results !== null) { ?>
	<?php
	$fastestRead = null;
	$fastestWrite = null;
	foreach ($results as $row) {
		if (isset($row['read']['ms']) && ($fastestRead === null || $row['read']['ms'] < $fastestRead)) {
			$fastestRead = $row['read']['ms'];
		}
		if (isset($row['write']['ms']) && ($fastestWrite === null || $row['write']['ms'] < $fastestWrite)) {
			$fastestWrite = $row['write']['ms'];
		}
	}
	?>
	<table class="table table-striped">
		<thead>
			<tr>
				<th>Engine</th>
				<th>Read (1000× ~100B)</th>
				<th>Write (1000× ~100B)</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($results as $engine => $row) { ?>
				<tr>
					<td><strong><?php echo h($engine); ?></strong></td>
					<?php foreach (['read', 'write'] as $op) { ?>
						<?php
						$cell = $row[$op] ?? null;
						$isFastest = $op === 'read'
							? (isset($cell['ms']) && $cell['ms'] === $fastestRead)
							: (isset($cell['ms']) && $cell['ms'] === $fastestWrite);
						?>
						<td<?php echo $isFastest ? ' class="table-success"' : ''; ?>>
							<?php if (isset($cell['error'])) { ?>
								<span class="text-danger">error: <?php echo h($cell['error']); ?></span>
							<?php } elseif (isset($cell['ms'])) { ?>
								<?php echo h(number_format($cell['ms'], 2)); ?> ms
								<br>
								<small><?php echo h(number_format($cell['opsPerSec'], 0)); ?> ops/s</small>
							<?php } else { ?>
								&mdash;
							<?php } ?>
						</td>
					<?php } ?>
				</tr>
			<?php } ?>
		</tbody>
	</table>

	<p><small>
		This is a single-pass micro-comparison on this host's current load. Take the absolute numbers with a pinch of salt; relative ordering is the useful signal.
	</small></p>
<?php } ?>

<?php if ($unavailable) { ?>
	<h3>Not available on this host</h3>
	<ul>
		<?php foreach ($unavailable as $engine => $entry) { ?>
			<li>
				<strong><?php echo h($engine); ?></strong>
				<?php $shortName = App::shortName($entry['className'], 'Cache/Engine', 'Engine'); ?>
				(<code><?php echo h($shortName); ?></code>)
				&mdash; <?php echo h($entry['reason'] ?? ''); ?>
			</li>
		<?php } ?>
	</ul>
<?php } ?>

</div>
