<h2>Settings</h2>

<?php if (!empty($settings)) { ?>
<h3>Current</h3>
<table class="list">
<tr>
	<th><?php echo __('Key');?></th>
	<th><?php echo __('Value');?></th>
	<th><?php echo __('Default');?></th>
	<th><?php echo __('Modified');?></th>
	<th class="actions"><?php echo __('Actions');?></th>
</tr>
<?php
foreach ($settings as $setting) {
	if (isset($keys[$setting['Setting']['key']])) {
		unset($keys[$setting['Setting']['key']]);
	}
?>
<tr>
	<td style="font-weight: bold;"><?php echo h($setting['Setting']['key']); ?></td>
	<td style="font-weight: bold;"><?php echo h($setting['Setting']['value']); ?></td>
	<td><?php echo h(Configure::read('Setting.' . $setting['Setting']['key'])); ?></td>
	<td><?php echo $this->Datetime->niceDate($setting['Setting']['modified']); ?></td>
	<td class="actions">
		<?php //echo $this->Html->link($this->Common->icon('view'), array('action'=>'view', $setting['Setting']['id']), array('escape'=>false)); ?>
		<?php echo $this->Html->link($this->Common->icon('edit'), array('action' => 'edit', $setting['Setting']['id']), array('escape' => false)); ?>
		<?php echo $this->Form->postLink($this->Common->icon('delete'), array('action' => 'delete', $setting['Setting']['id']), array('escape' => false), sprintf(__('Are you sure you want to delete # %s?'), $setting['Setting']['id'])); ?>
	</td>
</tr>
<?php
}
?>
</table>
<?php } ?>

<?php if (!empty($keys)) { ?>
<h3>Defaults</h3>
<table class="list">
<tr>
	<th><?php echo __('Key');?></th>
	<th><?php echo __('Value');?></th>
	<th class="actions"><?php echo __('Actions');?></th>
</tr>
<?php
foreach ($keys as $key) {
?>
<tr>
	<td><?php echo h($key); ?></td>
	<td><?php echo h(Configure::read('Setting.' . $key)); ?></td>
	<td class="actions">
		<?php //echo $this->Html->link($this->Common->icon('view'), array('action'=>'view', $setting['Setting']['id']), array('escape'=>false)); ?>
		<?php echo $this->Html->link($this->Common->icon('edit'), array('action' => 'add', $key), array('escape' => false)); ?>
	</td>
</tr>
<?php
}
?>
</table>
<?php } ?>

<?php if (!isset($keys)) { ?>
<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(sprintf(__('Add %s'), __('Setting')), array('action' => 'add')); ?></li>
	</ul>
</div>
<?php } ?>