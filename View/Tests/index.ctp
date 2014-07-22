<div class="">
<h2><?php echo __('Tests'); ?></h2>


<ul>
<li><?php  echo $this->Html->link(__d('setup', 'Is Mobile'), array('action' => 'is_mobile'));?></li>
<li><?php  echo $this->Html->link(__d('setup', 'HTML5 Basic Layout'), array('action' => 'html5'));?></li>
<li><?php  echo $this->Html->link(__d('setup', 'Text as image'), array('action' => 'text_as_image'));?></li>
<li><?php  echo $this->Html->link(__d('setup', 'Validation'), array('action' => 'validation'));?></li>

<li><?php  echo $this->Html->link(__d('setup', 'Access Results'), array('action' => 'access_results'));?>
<br />
Use this url for testing purposes (POST/GET, AJAX, browser headers, ...):
<pre style="font-weight: bold; background-color: gray; padding: 8px;"><?php echo $this->Html->url(array('action' => 'access'), true);?></pre>
</li>
</ul>


<br /><br />


<ul>
<li><?php  echo $this->Html->link(__d('setup', 'Admin Area'), array('admin' => true, 'action' => 'index'));?></li>
</ul>

</div>