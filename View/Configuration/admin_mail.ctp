<?php $this->Html->scriptStart(array('inline' => true)); ?>

jQuery(document).ready(function() {

	jQuery("#dropdown-subject").change(function () {
		var selvalue = jQuery(this).val();
		if (selvalue > 0) {
			jQuery("#selfdefined-subject").hide();
			//jQuery("#own-subject").val(selvalue);
		} else {
			jQuery("#selfdefined-subject").show();
			//jQuery("#own-subject").val('');
		}
	});

	jQuery("form input.submit").bind("dblclick", function(e) {
		e.preventDefault();
		return false;
	});

});
<?php $this->Html->scriptEnd(); ?>

<div class="floatRight">
<?php echo $this->Html->link(__('Reset'), array('action' => 'mail', 'reset' => 1));?>
</div>

<h2><?php echo __('Test Mail Configuration');?></h2>
Admin-Email: <?php echo $this->Format->encodeEmailUrl(Configure::read('Config.adminEmail')); ?> | Reply-Email: <?php echo $this->Format->encodeEmailUrl(Configure::read('Config.noReplyEmail')); ?> <br /><br />

<?php echo $this->Form->create('ContactForm', array('type' => 'file'));?>
	<fieldset>
		<legend><?php echo __('Connection');?></legend>
	<?php
	 echo $this->Form->input('Mail.smtp_host');
	 echo $this->Form->input('Mail.smtp_port');
	 echo $this->Form->input('Mail.smtp_username');
	 echo $this->Form->input('Mail.smtp_password');
	?>
	</fieldset>

	<fieldset>
		<legend><?php echo __('Email');?></legend>
	<?php
		echo $this->Form->input('from_email');
		echo $this->Form->input('from_name');

		echo $this->Form->input('reply_email');
		echo $this->Form->input('reply_name');
		echo BR;
		echo $this->Form->input('to_email');
		echo $this->Form->input('to_name');

		echo $this->Form->input('subject');

		//echo '<div id="selfdefined-subject" '.((!empty($this->Form->data['ContactForm']['dropdowns']) && $this->Form->data['Contact']['dropdowns']>0) ? 'style="display:none"' : '').'>';
		//echo '</div><br/>';
		echo $this->Form->input('message', array('type'=>'textarea', 'class'=>'form-control contact', 'rows'=>15));

		echo $this->Form->input('attachment', array('type'=>'file'));
	?>
	</fieldset>
<?php echo $this->Form->submit(__('Submit'), array('class'=>'submit'));?>
<?php echo $this->Form->end();?>