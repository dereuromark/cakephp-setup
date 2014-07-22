<div class="page view">
<h2><?php echo __('Bild-Test');?></h2>

<?php if (!empty($image)) { ?>
<h3>Resultat</h3>
<?php

$path = $this->Html->url('/img/content/tmp/');
$imageFiles['original'] = $path . 'original.jpg';
$imageFiles['thumb'] = $path . 'cropped.jpg';

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
	echo pre($image);
?>

<?php } ?>

<h3>Test-Bild-Upload</h3>
<?php echo $this->Form->create('Test', array('url' => '/' . $this->request->url, 'type' => 'file'));?>
	<fieldset>
		<legend><?php echo __('Bild hochladen');?></legend>

<?php
	echo $this->Form->input('file', array('type' => 'file', 'label' => 'Datei auswählen'));
?>
<br />
Hochgeladen werden können Bilder des Typs jpg, gif, png.


	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>


</div>

<br /><br />
<div class="actions">
	<ul>

	</ul>
</div>