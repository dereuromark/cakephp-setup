<h2>Sound-Test</h2>

<?php
	foreach ($types as $type) {
		echo $this->Html->link($type, array($type)) . ' ';
	}

	//$file = 'knocking.wav';
	$file = 'notify.mp3'; //yoda_message.mp3
?>

<h3>Aktuell</h3>
Typ: <b><?php echo $soundtype;?></b>


<h3>Status</h3>

<?php if ($soundtype == 'audio') { ?>

<div style="height:0;width:0">
	<audio src="<?php echo $this->Html->url('/files/sounds/' . $file, true)?>" autoplay="true">
	Your browser does not support the audio element.
	</audio>
</div>

OK:
<ul>
<li>FF3</li>
</ul>


<?php } elseif ($soundtype == 'sound') { ?>

<div style="height:0;width:0">
	<sound src="<?php echo $this->Html->url('/files/sounds/' . $file, true)?>" loop="0" delay="0"></sound>
</div>

OK:
<ul>
<li>...</li>
</ul>

<?php } elseif ($soundtype == 'object') { ?>

<div style="height:0;width:0">
	<object data="<?php echo $this->Html->url('/files/sounds/' . $file, true)?>" codetype="audio/wav"></object>
</div>

OK:
<ul>
<li>...</li>
</ul>

<?php } elseif ($soundtype == 'bgsound') { ?>

<div style="height:0;width:0">
	<bgsound src="<?php echo $this->Html->url('/files/sounds/' . $file, true)?>" loop="0">
</div>

OK:
<ul>
<li>IE6-8</li>
</ul>
NOT OK:
<ul>
<li>FF3</li>
</ul>

<?php } elseif ($soundtype == 'embed') { ?>

<div style="height:0;width:0">
	<embed src="<?php echo $this->Html->url('/files/sounds/' . $file)?>" autostart="true" hidden="true">
</div>

OK:
<ul>
<li>IE6, IE8</li>
<li>Chrome3</li>
<li>FF3</li>
</ul>
NOT OK:
<ul>
<li>(IE7) ActiveX-Meldung zum Wegklicken!</li>
</ul>

<?php } ?>