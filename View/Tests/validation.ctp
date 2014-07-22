<h2>Validation</h2>
required / notEmpty

<h3>Ergebnis</h3>
<?php
	echo pre($validationErrors);
?>

<h3>Test</h3>
<div>
<?php echo $this->Form->create('Configuration');?>

<?php
	echo $this->Form->input('required_string');

	echo $this->Form->input('not_allowed_empty_string');


	//echo $this->Form->input('required_string_gone');

	//echo $this->Form->input('not_allowed_empty_string_gone');


?>


<br />

<?php echo $this->Form->end(__('Submit'));?>
</div>