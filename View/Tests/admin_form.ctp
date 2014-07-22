<h2>Validation</h2>
required / notEmpty

<h3>Ergebnis</h3>
<?php
	echo pre($validationErrors);
?>

<h3>Test</h3>
<div>
<?php echo $this->Form->create('Configuration');?>

<fieldset>
	<legend>Validation</legend>
<?php
	$values = array(0 => 'bla', 1 => 'blub', '2' => 'foo', '3' => 'bar');

	echo $this->Form->input('select', array('empty' => '--', 'options' => $values));
	echo $this->Form->input('select_optional', array('empty' => '--', 'options' => $values));

	echo $this->Form->input('checkbox', array('empty' => '--', 'type' => 'checkbox'));
	echo $this->Form->input('checkbox_optional', array('empty' => '--', 'type' => 'checkbox'));

	unset($values[0]);

	echo $this->Form->input('radio', array('options' => $values, 'type' => 'radio'));
	echo $this->Form->input('radio_optional', array('options' => $values, 'type' => 'radio'));

	echo $this->Form->input('radio_xyz', array('options' => $values, 'type' => 'radio'));

	//echo $this->Form->input('required_string_gone');

	//echo $this->Form->input('not_allowed_empty_string_gone');


?>
</fieldset>

<fieldset>
	<legend>Form Style Test</legend>
<?php
	echo $this->Form->input('string', array('type' => 'text'));
	echo $this->Form->input('textbox', array('type' => 'textarea'));

	echo $this->Form->input('multiple_select', array('type' => 'select', 'multiple' => true, 'options' => $values));
	echo $this->Form->input('multiple_checkboxes', array('type' => 'select', 'multiple' => 'checkbox', 'options' => $values));

	echo $this->Form->input('datetime', array('type' => 'datetime', 'empty' => ' - - '));
	echo $this->Form->button('Reset Button Title', array('type' => 'reset'));
?>
</fieldset>



<?php echo $this->Form->end(__('Submit'));?>
</div>