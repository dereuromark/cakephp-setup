<div class="page form">

<h2>Bake</h2>
Notes:
<ul>
<li>If no primary key (id) is set, it will automatically add one as `aiid` (int 10 unsigned).</li>
<li>If no type is given for a field, it will default to `string` (varchar 255)</li>
<li>`created` and `modified` will be attached automatically</li>
</ul>

<?php if (isset($sql)) { ?>
<h3>SQL Statement</h3>
<pre>
<?php echo h($sql); ?>
</pre>
<?php } ?>

<h3>Available types</h3>
<ul>
<?php foreach ($types as $type => $sql) {
	echo '<li>';
	echo '<b>' . $type . '</b>' . ': ' . $sql;
	echo '</li>';
}
?>
</ul>

<h3>Todos</h3>
<ul>
<li>Support length, default/null, comments, ...</li>
<li>Support editing instead of replacing</li>
</ul>

<h3>Add Table or Tables</h3>
<?php echo $this->Form->create('Bake');?>
	<fieldset>
		<legend><?php echo __('Add');?></legend>
	<?php
		echo $this->Form->input('import', array('type' => 'textarea', 'style' => 'width: 99%', 'rows' => 20));

		echo $this->Form->input('auto_sort', array('type' => 'checkbox'));
		echo $this->Form->input('cleanup_and_validation_only', array('type' => 'checkbox'));
		echo $this->Form->input('delete_existing_tables', array('type' => 'checkbox'));

	?>
		<br style="clear: both"/>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('Back'), array('action' => 'index'));?></li>
	</ul>
</div>