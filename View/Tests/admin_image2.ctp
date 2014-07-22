<div class="page view">
<h2><?php echo __('Bild-Test');?></h2>

<pre>
<?php echo h($folder); ?>
</pre>

<?php if (!empty($result)) { ?>
<h3>Resultat</h3>
<?php

if (!empty($imageFiles)) {
	$height = 100;

	echo '<b>Bild-Vorschau</b>';

	echo '<div class="floatRight" style="margin-right: 2px;">';
	echo $this->Html->image($imageFiles['original'], array('height' => $height));
	echo '</div>';

	echo '<div class="floatRight" style="margin-right: 2px;">';
	echo $this->Html->image($imageFiles['thumb'], array('height' => $height));
	echo '</div>';
}
?>

<?php
	echo pre($result);
?>

<?php } ?>

<h3>Test-Bild</h3>
<?php
	if ($file) {
		echo h($file);
	} else {
		echo '<i>' . __('Please upload a file to the above folder') . '<i>';
	}
?>

</div>


<div class="actions">
	<ul>

	</ul>
</div>