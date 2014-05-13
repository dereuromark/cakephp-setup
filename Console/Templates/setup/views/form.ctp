<?php
/**
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2.0.5234
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<?php
	/** MOD for preventing addition of "Admin " **/
	$displayAction = Inflector::humanize($action);
	if (strpos($displayAction, 'Admin ') === 0) {
		$displayAction = ucfirst(trim(substr($displayAction, 6)));
	} else {
		$displayAction = ucfirst($action);
	}
?>
<div class="page form">
<h2><?php echo "<?php echo __('" . $displayAction . " %s', __('{$singularHumanName}')); ?>";?></h2>

<?php echo "<?php echo \$this->Form->create('{$modelClass}');?>\n";?>
	<fieldset>
		<legend><?php echo "<?php echo __('" . $displayAction . " %s', __('{$singularHumanName}')); ?>";?></legend>
<?php
	if (App::import('Model', $plugin . '.' . $modelClass) || App::import('Model', $modelClass)) {
		$relationModel = new $modelClass;
	}
	$skipFields = array('slug', 'lft', 'rght', 'created', 'modified', 'approved', 'deleted', 'created_by', 'modified_by', 'approved_by', 'deleted_by');
	if (isset($relationModel) && property_exists($relationModel, 'scaffoldSkipFields')) {
		$skipFields = array_merge($skipFields, (array)$relationModel->scaffoldSkipFields);
	}
?>
<?php
	echo "\t<?php\n";

	// display "empty" default value for belongsTo relations
	$relations = array();
	if (!empty($associations['belongsTo'])) {
		foreach ($associations['belongsTo'] as $rel) {
			$relations[] = $rel['foreignKey'];
		}
	}
	foreach ($fields as $field) {
		$emptyValue = "__('pleaseSelect')";
		if (!empty($schema[$field]['null'])) {
			$emptyValue = "__('noSelection')";
		}

		if (strpos($action, 'add') !== false && $field === $primaryKey) {
			continue;

		} elseif (in_array($field, $skipFields) || ($field === 'sort' && $upDown)) {
			continue;

		} elseif (in_array($field, $relations) || in_array($schema[$field]['type'], array('time', 'date', 'datetime'))) {
			$options = array();
			if (in_array($field, $relations)) {
				$options[] = "'empty' => Configure::read('Select.defaultBefore') . $emptyValue . Configure::read('Select.defaultAfter')";
			} else {
				$options[] = "'empty' => '- -'";
			}
			if ($schema[$field]['type'] === 'datetime' || $schema[$field]['type'] === 'date') {
				$options[] = "'dateFormat' => 'DMY'";
			}
			if ($schema[$field]['type'] === 'datetime' || $schema[$field]['type'] === 'time') {
				$options[] = "'timeFormat' => 24";
			}
			$options = implode(', ', $options);

			echo "\t\techo \$this->Form->input('{$field}', array({$options}));\n";

		} elseif ($schema[$field]['type'] === 'integer' && method_exists($modelClass, $enumMethod = lcfirst(Inflector::camelize(Inflector::pluralize($field))))) {
			echo "\t\techo \$this->Form->input('{$field}', array('options' => " . Inflector::camelize($modelClass) . "::" . $enumMethod . "(), 'empty' => Configure::read('Select.defaultBefore') . $emptyValue . Configure::read('Select.defaultAfter')));\n";

		} else {
			echo "\t\techo \$this->Form->input('{$field}');\n";
		}
	}
	//TODO: for relations add: array('empty'=>Configure::read('Select.default_before').__('pleaseSelect').Configure::read('Select.default_after'))
	if (!empty($associations['hasAndBelongsToMany'])) {
		foreach ($associations['hasAndBelongsToMany'] as $assocName => $assocData) {
			echo "\t\techo \$this->Form->input('{$assocName}');\n";
		}
	}
	echo "\t?>\n";
?>
	</fieldset>
<?php
	echo "<?php echo \$this->Form->submit(__('Submit')); ?>\n";
	echo "<?php echo \$this->Form->end(); ?>\n";
?>
</div>

<div class="actions">
	<ul>
<?php if (strpos($action, 'add') === false): ?>
		<li><?php echo "<?php echo \$this->Form->postLink(__('Delete'), array('action' => 'delete', \$this->Form->value('{$modelClass}.{$primaryKey}')), array(), __('Are you sure you want to delete # %s?', \$this->Form->value('{$modelClass}.{$primaryKey}'))); ?>";?></li>
<?php endif;?>
		<li><?php echo "<?php echo \$this->Html->link(__('List %s', __('{$pluralHumanName}')), array('action' => 'index'));?>";?></li>
<?php
		$done = array();
		foreach ($associations as $type => $data) {
			// We dont need them
			break;
			foreach ($data as $alias => $details) {
				if (Inflector::camelize($details['controller']) !== $this->name && !in_array($details['controller'], $done)) {
					echo "\t\t<li><?php echo \$this->Html->link(__('List %s', __('" . Inflector::humanize($details['controller']) . "')), array('controller' => '{$details['controller']}', 'action' => 'index')); ?> </li>\n";
					$done[] = $details['controller'];
				}
			}
		}
?>
	</ul>
</div>