<style>
ul.none {
	list-style: none;
	list-style-position: outside;
}
ul.none li {
	padding-top: 10px;
	padding-bottom: 20px;
}
</style>

<div class="page index">
<h2>Setup</h2>

<h3>HTML Form Elements</h3>
<ul class="none">
<li><?php echo $this->Form->input('input'); ?></li>
<li><?php echo $this->Form->text('text'); ?></li>
<li><?php echo $this->Form->textarea('textarea'); ?></li>
<li><?php echo $this->Form->select('select', array(' - xyz - ')); ?></li>
<li><?php echo $this->Form->checkbox('checkbox'); ?></li>
<li><?php echo $this->Form->radio('radio'); ?></li>
<li><?php echo $this->Form->meridian('meridian'); ?></li>
<li><?php echo $this->Form->minute('minute'); ?></li>
<li><?php echo $this->Form->hour('hour'); ?></li>
<li><?php echo $this->Form->day('day'); ?></li>
<li><?php echo $this->Form->month('month'); ?></li>
<li><?php echo $this->Form->year('year'); ?></li>
<li><?php echo $this->Form->file('file'); ?></li>
<li><?php echo $this->Form->dateTime('date_time'); ?></li>
<li><?php echo $this->Form->password('password'); ?></li>

<li><?php echo $this->Form->hidden('hidden'); ?></li>

<li><?php echo $this->Form->submit(__('Submit')); ?></li>
<li><?php echo $this->Form->button(__('Button')); ?></li>
<li><?php echo $this->Html->link(__('Link Button'), 'javascript:void(0)', array('class' => 'button')); ?></li>
</ul>

<?php
$options = array(
	1, 2, 3
)
?>
<h3>Multiple</h3>
<ul class="none">
<li><?php echo $this->Form->input('multiple', array('type' => 'select', 'options' => $options, 'multiple' => true)); ?></li>
<li><?php echo $this->Form->input('multiple_checkbox', array('type' => 'select', 'options' => $options, 'multiple' => 'checkbox')); ?></li>
<li><?php echo $this->Form->input('multiple_radio', array('type' => 'radio', 'options' => $options)); ?></li>
</ul>

<h3>Multiple - disabled</h3>
<ul class="none">
<?php
	$disabled = array(2);
?>
<li><?php echo $this->Form->input('multiple', array('type' => 'select', 'options' => $options, 'disabled' => $disabled, 'multiple' => true)); ?></li>
<li><?php echo $this->Form->input('multiple_checkbox', array('type' => 'select', 'options' => $options, 'disabled' => $disabled, 'multiple' => 'checkbox')); ?></li>
<li><?php echo $this->Form->input('multiple_radio', array('type' => 'radio', 'options' => $options, 'disabled' => $disabled)); ?></li>
</ul>

<h3>Own</h3>
<ul></ul>
<li><?php echo $this->Form->linkButton(__('Link Button')); ?></li>
</ul>


<h3>HTML5 Form Elements + POST result of them</h3>
... not yet fully implemented

<?php echo $this->Form->create('Test'); ?>

<ul class="none">
<li><?php echo $this->Form->input('email_with_placeholder', array('type' => 'email', 'placeholder' => 'email address')); ?></li>
<li><?php echo $this->Form->input('url', array('type' => 'url')); ?></li>
<li><?php echo $this->Form->input('email', array('type' => 'email')); ?></li>
<li><?php echo $this->Form->input('tel', array('type' => 'tel')); ?></li>
<li><?php echo $this->Form->input('integer', array('type' => 'number')); ?></li>
<li><?php echo $this->Form->input('number', array('type' => 'number', 'step' => 'any')); ?></li>
<li><?php echo $this->Form->input('range', array('type' => 'range')); ?></li>
<li><?php echo $this->Form->input('week', array('type' => 'week')); ?></li>
<li><?php echo $this->Form->input('color', array('type' => 'color')); ?></li>
</ul>
<?php echo $this->Form->end(__('Submit')); ?>

</div>