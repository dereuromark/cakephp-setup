<div class="page form">
<h2>Time Stuff</h2>


<h3>Timestamps</h3>
Make timestamps human readable

<?php echo $this->Form->create('Configuration');?>
	<fieldset>
		<legend><?php echo __('Revert timestamp');?></legend>
	<?php
		echo $this->Form->input('Form.timestamp');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>

<?php
	if (isset($this->request->data['Form']['timestamp'])) {
		echo '<div class="label">' . __('Result') . ':' . '</div>';
		echo '<div class="result" style="font-weight: bold; color: green; margin-bottom: 10px;">';
		echo $this->Datetime->niceDate($this->request->data['Form']['timestamp'], FORMAT_NICE_YMDHMS);
		echo '</div>';
		echo '<div style="clear: both">';
		echo $this->Datetime->relLengthOfTime($this->request->data['Form']['timestamp']);
		echo '</div>';
	}
?>

</div>