<?php
//$this->Jquery->plugins(array('growfield'));
echo $this->Common->script('Tools.Jquery|plugins/jquery.growfield2');
?>
<style>
textarea.resizable {
	width: 98%
}
</style>
<script type="text/javascript">

	/* jQuery textarea resizer plugin usage */
	$(document).ready(function() {

		$('.resizable').growfield( {
			min: 100,
			max: 600,
			animate: false,
			speed: 1,
			restore: false
			}
		);

	});
</script>


<h2>Sql</h2>
Dumb, Backup, Restore etc

<br />

<h3>Restore</h3>

<?php
if (!empty($queries) && is_array($queries)) {
	echo '<a href="javascript:void(0)" onclick="toggleMe(\'queryresult\')">Show all inserted queries</a>';
	echo '<div class="example "id="queryresult" style="display:none"><ul>';
	foreach ($queries as $query) {
		//echo '<li>'.$this->Geshi->highlight($query, 'sql').'</li>';
		echo '<li>' . $query . '</li>';
	}
	echo '</ul></div><br /><br />';
}
?>

<b>From File:</b><br />
<div>select</div>

<br /><br />
<b>Or Manually:</b>
<?php echo $this->Form->create('Configuration');?>
	<fieldset>
		<legend><?php echo __('Insert Sql');?></legend>
	<?php
		echo $this->Form->input('sql', array('type' => 'textarea', 'label' => false, 'class' => 'insert_sql resizable'));
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>


<br />
<h3>Backup</h3>
File Saving and Downloading