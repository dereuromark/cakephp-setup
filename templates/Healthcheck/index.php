<?php
/**
 * @var \App\View\AppView $this
 * @var bool $passed
 * @var array $result
 * @var array<string> $domains
 * @var int $errors
 * @var int $warnings
 */

// Make sure to noindex,nofollow this page

use Cake\Utility\Inflector;

?>

<h1>
	<?php echo $this->element('Setup.yes_no', ['value' => $passed])?>
	<?php echo $passed ? 'PASS' : 'FAIL'; ?>
</h1>

<div>
	<ul class="list-inline">
		<li class="list-inline-item">Filter: </li>

		<?php if ($this->request->getQuery('domain')) { ?>
		<li class="list-inline-item"><?php echo $this->Html->link('ALL', ['?' => ['domain' => null]]); ?></li>
		<?php } ?>

		<?php foreach ($domains as $domain) { ?>
		<li class="list-inline-item"><?php echo $this->Html->link($domain, ['?' => ['domain' => $domain]]); ?></li>
		<?php } ?>
	</ul>
</div>

	<h2>Result</h2>

<p>
	<?php if ($errors) { ?>
		<span class="text-danger" style="margin-right: 10px">Errors: <?php echo h($errors); ?></span>
	<?php } ?>
	<?php if ($warnings) { ?>
		<span class="text-warning" style="margin-right: 10px">Warnings: <?php echo h($warnings); ?></span>
	<?php } ?>
</p>

<?php
/**
 * @var string $domain
 * @var array<\Setup\Healthcheck\Check\CheckInterface> $checks
 */
foreach ($result as $domain => $checks) {
	?>
	<section class="section-tile">
		<h3><?php echo h(Inflector::humanize(Inflector::underscore($domain))); ?></h3>
		<ul>
			<?php foreach ($checks as $check) { ?>
				<li class="<?php echo h($check->passed() ? 'passed' : ($check->level() === $check::LEVEL_ERROR ? 'failed' : 'warn')); ?>">
					<b><?php echo h($check->name()); ?></b>
					<?php if (!$check->passed()) { ?>
						<?php if ($check->failureMessage()) { ?>
							<div class="alert alert-danger"><?php echo h(implode(', ', $check->failureMessage())); ?></div>
						<?php } ?>
						<?php if ($check->warningMessage()) { ?>
							<div class="alert alert-warning"><?php echo h(implode(', ', $check->warningMessage())); ?></div>
						<?php } ?>
					<?php } else { ?>
						<?php if ($check->successMessage()) { ?>
							<div class="alert alert-success"><?php echo h(implode(', ', $check->successMessage())); ?></div>
						<?php } ?>
					<?php } ?>
					<?php if ($check->infoMessage()) { ?>
						<details>
							<summary>Info</summary>
							<ul>
								<?php foreach ($check->infoMessage() as $key => $value) { ?>
									<li><?php echo h($value); ?></li>
								<?php } ?>
							</ul>
						</details>
					<?php } ?>
				</li>
			<?php } ?>
		</ul>
	</section>
<?php } ?>


<?php if (count($result) === 0) { ?>
	<p class="empty">No checks found.</p>
<?php } ?>

<style>
	li.passed b {
		background-color: #ddf3e0;
	}
	li.failed b {
		background-color: #ffbca4;
	}
	li.warn b {
		background-color: #fff3cd;
	}
</style>
