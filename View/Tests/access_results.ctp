<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('.details').click(function() {
		jQuery(this).parent('td').children('div').toggle();
	});
});
</script>

<h2>Access Results</h2>
Die Farbe wechselt mit der session id!
<br /><br />

<table>
<?php
	foreach ($results as $result) {
		$color = $this->Test->color(!empty($result['session']) ? $result['session'] : '');
		echo '<tr style="background-color: ' . $color . '">';
		echo '<td style="background-color: inherit">' . $this->Datetime->niceDate($result['time'], FORMAT_NICE_YMDHMS) . '</td>';

		$own = '';
		if ($this->Test->own($result['ip'])) {
			$own = BR . '<b>(DU)</b>';
		}

		echo '<td style="background-color: inherit">' . h($result['ip']) . BR . h($result['host']) . $own . '</td>';

		$geo = '<i>no geo data</i>';
		if (!empty($result['geodata'])) {
			$country = $result['geodata']['country_code']; //country_name
			$city = $result['geodata']['city']; //lat/lng

			$geo = array();
			if (!empty($city)) {
				$geo[] = $city;
			} else {
				$geo[] = $result['geodata']['lat'] . ', ' . $result['geodata']['lng']; //TODO: googleV3 helper!
			}
			$geo[] = '<span title="' . h($result['geodata']['country_name']) . '">' . $country . '</span>';
			$geo = implode(', ', $geo);
		}

		//TODO: details view in popup
		if (empty($result['server']['HTTP_USER_AGENT'])) {
			$result['server']['HTTP_USER_AGENT'] = '- n/a -';
		}

		echo '<td style="background-color: inherit">' . h($result['server']['REQUEST_METHOD']) . BR . h($result['server']['HTTP_USER_AGENT']) . BR . $geo . '
			' . BR . $this->Format->cIcon(ICON_DETAILS, 'Details', null, false, array('class' => 'details hand')) . '
			<div style="display: none;">' . pre($result) . '</div>
		</td>';
		echo '</tr>';
	}
?>
</table>

<?php
	//pr($results);
?>