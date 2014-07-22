<?php
//$this->Html->script('https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js', array('inline'=>false));
//'Tools.Jquery|jquery',
//$this->Common->script(array('Tools.Jquery|mobile/jquery.mobile'), array('inline'=>false));
$this->Common->css(array('Tools.Jquery|mobile/jquery.mobile'), array('inline' => false));
?>

<style>
div.input.date div.ui-select {
	width: 200px;
}
form.ui-listview-filter {
	margin-bottom: 0px;
}
</style>

<h1>HTML5 Test Site</h1>

<h2>Details</h2>
<details>
<summary>Copyright 1999-2011.</summary>
<p> - blabla. All Rights Reserved.</p>
<p>All content and graphics on this web site are the property of the company blabla.</p>
</details>

<h2>Meter</h2>
<meter min="0" max="100" value="25"></meter>

<h2>Progress</h2>
<progress value="250" max="1000">
<span id="downloadProgress">25</span>%
</progress>

<h2>Video</h2>
<video src="http://www.quackit.com/video/pass-countdown.ogg" width="300" height="150" controls>
<p>If you are reading this, it is because your browser does not support the HTML5 video element.</p>
</video>

<h2>List View</h2>

<ul data-role="listview" data-inset="true" data-filter="true">
	<li><a href="#">Acura</a></li>
	<li><a href="#">Audi</a></li>
	<li><a href="#">BMW</a></li>
	<li><a href="#">Cadillac</a></li>
	<li><a href="#">Ferrari</a></li>
</ul>


<h2>Button</h2>
<a href="#" data-role="button" data-icon="star">Star button</a>


<h2>Form</h2>
<?php
echo $this->Form->create('Test', array(
	'inputDefaults' => array(
	'div' => array('data-role' => 'fieldcontain')
	),
));
echo $this->Form->input('some_text', array('type' => 'text'));
echo $this->Form->input('some_textarea', array('type' => 'textarea'));
echo $this->Form->input('some_number', array('type' => 'number', 'step' => 'any'));
echo $this->Form->input('some_number_10', array('type' => 'number', 'step' => 10));

echo $this->Form->input('some_date', array('type' => 'date', 'dateFormat' => 'DMY'));
echo $this->Form->time('some_time', array('timeFormat' => 24));

echo $this->Form->input('some_checkboxes', array('type' => 'select', 'multiple' => 'checkbox', 'options' => array(1, 2, 3)));
echo $this->Form->input('some_radio', array('type' => 'radio', 'multiple' => 'radio', 'options' => array(1, 2, 3)));
echo $this->Form->input('some_select', array('type' => 'select', 'multiple' => true, 'options' => array(1, 2, 3)));

$value = null;
if (empty($this->request->data)) {
	$value = 25;
}
echo $this->Form->input('some_slider', array('type' => 'range', 'min' => 0, 'value' => $value, 'max' => 100));

echo $this->Form->end(__('Submit'));
?>

<!--

<div data-role="page">

	<div data-role="header">
		<h1>My Title</h1>
	</div>

	<div data-role="content">
		<p>Hello world</p>
	</div>

</div>

 -->