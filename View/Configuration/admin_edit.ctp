<div class="page form">
<?php echo $this->Form->create('Configuration');?>
	<fieldset>
		<legend><?php echo __('Edit %s', __('Configuration'));?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('admin_name');
		echo $this->Form->input('admin_email');
		echo $this->Form->input('admin_emailname');
		echo $this->Form->input('page_name');
		echo $this->Form->input('guest_register');
		echo $this->Form->input('debugging');
		echo $this->Form->input('dberror_mail');
		echo $this->Form->input('dberror_show');
		echo $this->Form->input('max_loginfail');
		echo $this->Form->input('max_emails');
		echo $this->Form->input('pw_minlength');
		echo $this->Form->input('timeout');
		echo $this->Form->input('std_vis');
		echo $this->Form->input('website_startdate', array('dateFormat' => 'DMY'));
		echo $this->Form->input('active');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $this->Form->value('Configuration.id')), array('escape' => false), __('Are you sure you want to delete # %s?', $this->Form->value('Configuration.id'))); ?></li>
		<li><?php echo $this->Html->link(__('List %s', __('Configurations')), array('action' => 'index'));?></li>
	</ul>
</div>