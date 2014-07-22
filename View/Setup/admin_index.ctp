<h2>Setup</h2>

<h3>Configuration</h3>
<ul>
<li><?php echo $this->Html->link(__('Overview'), array('controller'=>'configuration', 'action'=>'index')); ?></li>
<li><?php echo $this->Html->link(__('Install'), array('admin'=>false, 'controller'=>'install', 'action'=>'index')); ?></li>
</ul>

<h3>Tools</h3>
<ul>
<li><?php echo $this->Html->link(__('Timestamps'), array('controller'=>'utils', 'action'=>'time')); ?></li>
<li><?php echo $this->Html->link(__('Geo IP-Lookup'), array('controller'=>'utils', 'action'=>'geo')); ?></li>
</ul>

<h3>Resources</h3>
<ul>
<li><?php echo $this->Html->link('Coding Standards', array('action'=>'coding_standards')); ?></li>
<li><?php echo $this->Html->link('XHTML Elements', array('action'=>'xhtml_elements')); ?></li>
<li><?php echo $this->Html->link('Form Elements', array('action'=>'form_elements')); ?></li>
<li><?php echo $this->Html->link('Flash Messages', array('action'=>'flash_messages')); ?></li>

<li><?php echo $this->Html->link('Tests', array('controller'=>'tests', 'action'=>'index')); ?></li>
</ul>