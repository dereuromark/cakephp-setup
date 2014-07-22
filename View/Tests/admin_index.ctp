<h2><?php echo __('Tests'); ?></h2>

<h3>Setup</h3>
<ul>
<li><?php echo $this->Html->link('Test Email', array('action' => 'test_mail')); ?></li>
<li><?php echo $this->Html->link('Redirect / Url Setup', array('action' => 'redirect')); ?></li>
<li><?php echo $this->Html->link('Caching', array('action' => 'caching')); ?></li>
</ul>


<h3>Tools</h3>
<ul>
<li><?php echo $this->Html->link('Encode Decode', array('action' => 'encode_decode')); ?></li>
<li><?php echo $this->Html->link('Security', array('action' => 'security')); ?></li>
<li><?php echo $this->Html->link('Cookietest', array('action' => 'cookietest')); ?></li>
</ul>

<h3>Misc</h3>
<ul>
<li><?php echo $this->Html->link('HTML Sound', array('action' => 'sound')); ?></li>
<li><?php echo $this->Html->link('Old Browser Alert', array('action' => 'old_browser_alert_test')); ?></li>
</ul>



<h3>Collecting Information</h3>

<ul>
<li><?php echo $this->Html->link('HP honeypot', array('admin'=>false, 'action'=>'hp')); ?></li>
<li><?php echo $this->Html->link('NS no script', array('admin'=>false, 'action'=>'ns')); ?></li>
</ul>