<?php
/**
 * @var \App\View\AppView $this
 * @var array<string> $plugins
 * @var array $classes
 * @var array<string, array<string, string>> $map
 */
?>

<h1>TypeMap overview</h1>

<div class="row">
	<div class="col-md-8">
		<h2>Mapped types</h2>

		<table class="table">
			<tr>
				<th>Type</th>
				<th>Name</th>
				<th>Class</th>
            </tr>
			</tr>
			<?php
			foreach ($map as $type => $info) {
				?>
				<tr>
					<td>
						<?php echo h($type); ?>
					</td>
					<td>
						<span><?php echo h($info['name']); ?></span>
					</td>
					<td>
						<small><code><?php echo h($info['class']); ?></code></small>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
	</div>
	<div class="col-md-4">
		<h2>Available classes</h2>
		<p>The following <?php echo count($plugins) ?> <?php echo ($this->request->getQuery('all') ? '' : 'loaded'); ?> plugins have been searched:
		<br>
			<small><?php echo implode(', ', $plugins)?></small>
		</p>

		<p>The following not (yet) used type classes have been found:</p>
		<ul>
		<?php foreach ($classes as $namespace => $classNames) { ?>
		<li>
			<?php echo h($namespace); ?>
			<ul>
				<?php foreach ($classNames as $name => $className) { ?>
				<li>
					<?php echo h($name); ?>
					<div>
						<small><code><?php echo h($className); ?></code></small>
					</div>
				</li>
				<?php } ?>
			</ul>
		</li>
		<?php } ?>
		</ul>

		<p><?php echo ($this->request->getQuery('all') ? $this->Html->link('Check loaded plugins only', ['?' => []]) : $this->Html->link('Check all available plugins', ['?' => ['all' => true]])); ?></p>
	</div>
</div>
