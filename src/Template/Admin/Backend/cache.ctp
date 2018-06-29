<?php
/**
 * @var \App\View\AppView $this
 */
?>

<div class="index col-md-12">

<h2>Cache Config</h2>

<div class="actions">
<ul>
	<?php foreach ($caches as $key => $config) { ?>
	<li>
		<h3><?php echo h($key); ?></h3>
		<pre><?php echo print_r($config); ?></pre>
	</li>
	<?php } ?>
</ul>
</div>

</div>
