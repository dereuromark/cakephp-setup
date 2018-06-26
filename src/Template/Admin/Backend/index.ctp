<?php
/**
 * @var \App\View\AppView $this
 */
use Cake\Core\Configure;
use SetupExtra\Lib\DebugLib;
use SetupExtra\Lib\SystemLib;
?>

<div class="index col-md-12">

<h2>Backend</h2>

<div class="actions">
<ul>
	<li><?php echo $this->Html->link(__('PHP Info (Full)'), ['action' => 'phpinfo']); ?></li>
	<li><?php echo $this->Html->link(__('Session'), ['action' => 'session']);?></li>
	<li><?php echo $this->Html->link(__('Database Size'), ['action' => 'database']);?></li>
</ul>
</div>

</div>
