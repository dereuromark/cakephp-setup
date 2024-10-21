<?php
/**
 * @var \App\View\AppView $this
 */
use Cake\Core\Configure;
?>

<div class="columns col-md-12">

<h1>Backend</h1>

<div class="actions">
<ul>
	<li><?php echo $this->Html->link(__('PHP Info (Full)'), ['action' => 'phpinfo']); ?></li>
	<li><?php echo $this->Html->link(__('Session'), ['action' => 'session']);?></li>
	<li><?php echo $this->Html->link(__('Cache'), ['action' => 'cache']);?></li>
	<li><?php echo $this->Html->link(__('Database Size'), ['action' => 'database']);?></li>
	<li><?php echo $this->Html->link(__('ENV Config'), ['action' => 'env']);?></li>
</ul>
</div>

</div>
