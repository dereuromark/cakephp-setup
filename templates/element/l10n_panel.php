<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $values
 */

if (!isset($values)) {
	$values = [];
}

?>

<section class="section-tile">
	<h1>Localization</h1>

	<h2>Datetime/Date/Time</h2>

	<ul>
		<?php foreach ($values as $name => $value) { ?>
		<li>
			<?php echo h($name); ?>: <?php echo (string)$value; ?> (<?php echo get_class($value)?>)
		</li>
		<?php } ?>
	</ul>

</section>
