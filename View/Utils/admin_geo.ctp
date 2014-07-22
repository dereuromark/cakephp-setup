<div class="page form">
<?php echo $this->Form->create('Configuration');?>
	<fieldset>
		<legend><?php echo __('Get IP Address Information');?></legend>
	<?php
		echo $this->Form->input('Form.ip', array('label' => __('IpAddress')));
	?>
	Leave empty for own ip address
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>

<br />
<h3><?php echo $ip;?></h3>

<div class="info">
	<ul>
<?php
if (!empty($ip)) {

if (!empty($geoValues)) {
	foreach ($geoValues as $name => $value) {
		echo '<li>';
		echo $name . ': ' . $value;
		echo '</li>';
	}

	if (isset($geoValues['lng']) && isset($geoValues['lat'])) {
		$this->loadHelper('Tools.GoogleMapV3');
		echo $this->GoogleMapV3->staticMap(array('size' => '640x600', 'zoom' => 12, 'markers' => $this->GoogleMapV3->staticMarkers(array(array('lat' => $geoValues['lat'], 'lng' => $geoValues['lng'])))));
	}

} else {
	echo '<li class="notAvailable">kein Ergebnis (IP nicht gültig etc)</li>';
}

} else {
	echo '<li class="notAvailable">keine IP angegeben</li>';
}
?>
	</ul>
</div>

<br />

<h3>Nearby Places</h3>

<div class="info">
	<ul>
<?php
if (!empty($nearbyPlaces)) {

foreach ($nearbyPlaces as $nr => $value) {
	echo '<li>';
	echo $value['city'] . ' (' . $value['region_name'] . '): LAT ' . $value['lat'] . ' LNG ' . $value['lng'] . ' [' . $value['distance'] . 'km]';
	echo '</li>';
}

} else {
	echo '<li class="notAvailable">keine Orte in der Naehe gefunden</li>';
}
?>
	</ul>
</div>