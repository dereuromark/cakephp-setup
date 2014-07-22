<h2><?php echo __('PostLink'); ?></h2>

<h3>Different types of postLinks</h3>
<ul>
<li><?php echo $this->Form->postLink('POST ME', array()); ?></li>
<li><?php echo $this->Form->deleteLink('DELETE ME', array()); ?></li>
<li><?php echo $this->Form->postLink('PUT ME', array(), array('method' => 'PUT')); ?></li>
<li><?php echo $this->Form->postLink('GET ME', array(), array('method' => 'GET')); ?></li>
</ul>

<h3>Results</h3>
<ul>
<li>
<?php
echo $this->Format->ok('GET', $this->request->is('get'));
?>
</li>

<li>
<?php
echo $this->Format->ok('POST', $this->request->is('post'));
?>
</li>

<li>
<?php
echo $this->Format->ok('PUT', $this->request->is('put'));
?>
</li>

<li>
<?php
echo $this->Format->ok('DELETE', $this->request->is('delete'));
?>
</li>

</ul>
