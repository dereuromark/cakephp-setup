
<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\I18n\DateTime $time
 * @var string $timezone
 * @var \Tools\Model\Entity\Token|null $token
 * @var string|null $dateTimeString
 */

?>
<div class="columns col-md-12">

<h1>Timezones</h1>

	<h2>Current</h2>
	<ul>
		<li>
			Time: <?php echo $this->Time->nice($time); ?> (<?php echo h(get_class($time)); ?>)
		</li>
		<li>
			Default string cast: <?php echo h($time); ?>
		</li>
	</ul>

	<?php if (isset($token)) { ?>
	<h3>Database</h3>

		<p>String: <code><?php echo h($dateTimeString); ?></code></p>

		<ul>
			<li>
				nice(): <?php echo $this->Time->nice($token->created); ?> (<?php echo h($token->created->timezone->getName()); ?>)
			</li>
			<li>
				format(): <?php echo $this->Time->format($token->created); ?> (<?php echo h($token->created->timezone->getName()); ?>)
			</li>
			<li>
				Tools.niceDate(): <?php echo $this->Time->niceDate($token->created); ?> (<?php echo h($token->created->timezone->getName()); ?>)
			</li>
		</ul>


		<?php echo $this->Form->create($token);?>
		<fieldset>
			<legend><?php echo __('Test forms'); ?></legend>
			<?php
			echo $this->Form->control('created', ['label' => 'DateTime']);
			?>
		</fieldset>
		<?php if (isset($result)) { ?>
			<fieldset>
				<legend><?php echo __('Result');?></legend>
				<?php
				echo pre($result);
				?>
			</fieldset>
		<?php } ?>

		<?php echo $this->Form->submit(__('Submit')); echo $this->Form->end();?>

	<?php } ?>

</div>
