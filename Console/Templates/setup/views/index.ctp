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
<div class="page index">
	<h2><?php echo "<?php echo __('{$pluralHumanName}');?>";?></h2>

	<table class="table list">
		<tr>
<?php
	if (App::import('Model', $plugin . '.' . $modelClass) || App::import('Model', $modelClass)) {
		$relationModel = new $modelClass;
	}
	$skipFields = array('id', 'password', 'slug', 'lft', 'rght', 'created_by', 'modified_by', 'approved_by', 'deleted_by');
	if (isset($relationModel) && property_exists($relationModel, 'scaffoldSkipFieldsIndex')) {
		$skipFields = array_merge($skipFields, (array)$relationModel->scaffoldSkipFieldsIndex);
	}
	if (isset($relationModel) && property_exists($relationModel, 'scaffoldSkipFields')) {
		$skipFields = array_merge($skipFields, (array)$relationModel->scaffoldSkipFields);
	}
?>
<?php
	foreach ($fields as $field):
	// Don't display primaryKeys
		if (in_array($field, $skipFields) || (!empty($schema[$field]['key']) && $schema[$field]['key'] === 'primary') || ($field === 'sort' && $upDown)) {
		continue;
	}
?>
				<th><?php echo "<?php echo \$this->Paginator->sort('{$field}');?>";?></th>
<?php endforeach;?>
		<th class="actions"><?php echo "<?php echo __('Actions');?>";?></th>
	</tr>
<?php
	echo "<?php
foreach (\${$pluralVar} as \${$singularVar}) { ?>\n";
	echo "\t<tr>\n";
	foreach ($fields as $field) {
		// Don't display primaryKeys
		if (in_array($field, $skipFields) || (!empty($schema[$field]['key']) && $schema[$field]['key'] === 'primary') || ($field === 'sort' && $upDown)) {
			continue;
		}

		$isKey = false;
		if (!empty($associations['belongsTo'])) {
			foreach ($associations['belongsTo'] as $alias => $details) {
				if ($field === $details['foreignKey']) {
					$isKey = true;
					echo "\t\t<td>\n\t\t\t<?php echo \$this->Html->link(\${$singularVar}['{$alias}']['{$details['displayField']}'], array('controller' => '{$details['controller']}', 'action' => 'view', \${$singularVar}['{$alias}']['{$details['primaryKey']}'])); ?>\n\t\t</td>\n";
					break;
				}
			}
		}
		if ($isKey !== true) {
			if ($field === 'created' || $field === 'modified' || $schema[$field]['type'] === 'datetime') {
				// Localize date/time output
				echo "\t\t<td>\n\t\t\t<?php echo \$this->Datetime->niceDate(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";

			} elseif ($schema[$field]['type'] === 'date') {
				// Localize date only output
				echo "\t\t<td>\n\t\t\t<?php echo \$this->Datetime->niceDate(\${$singularVar}['{$modelClass}']['{$field}'], FORMAT_NICE_YMD); ?>\n\t\t</td>\n";

			} elseif ($schema[$field]['type'] === 'boolean') {
				// Boolean Yes/No images
				echo "\t\t<td>\n\t\t\t<?php echo \$this->Format->yesNo(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";

			} elseif ($schema[$field]['type'] === 'text') {
				// Newlines in textareas
				echo "\t\t<td>\n\t\t\t<?php echo nl2br(h(\${$singularVar}['{$modelClass}']['{$field}'])); ?>\n\t\t</td>\n";

			} elseif ($schema[$field]['type'] === 'integer' && method_exists($modelClass, $enumMethod = lcfirst(Inflector::camelize(Inflector::pluralize($field))))) {
				// Handle Tools "enums"
				echo "\t\t<td>\n\t\t\t<?php echo " . $modelClass . "::" . $enumMethod . "(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";

			} elseif ($schema[$field]['type'] === 'decimal' || $schema[$field]['type'] === 'float' && strpos($schema[$field]['length'], ',2') !== false) {
				// Money formatting
				echo "\t\t<td>\n\t\t\t<?php echo \$this->Numeric->money(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";

			} elseif ($schema[$field]['type'] === 'float' && strpos($schema[$field]['length'], ',') !== false) {
				// Generic float value handling
				echo "\t\t<td>\n\t\t\t<?php echo \$this->Numeric->format(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";

			} else {
				// Protection against js injection by using h() function)
				echo "\t\t<td>\n\t\t\t<?php echo h(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";
			}
		}
	}

		echo "\t\t<td class=\"actions\">\n";

	// Sortable Behavior buttons
	if (!empty($upDown)) {
		echo "\t\t\t<?php echo \$this->Html->link(\$this->Format->icon('up'), array('action' => 'up', \${$singularVar}['{$modelClass}']['{$primaryKey}']), array('escape' => false)); ?>\n";
		echo "\t\t\t<?php echo \$this->Html->link(\$this->Format->icon('down'), array('action' => 'down', \${$singularVar}['{$modelClass}']['{$primaryKey}']), array('escape' => false)); ?>\n";
	}

	echo "\t\t\t<?php echo \$this->Html->link(\$this->Format->icon('view'), array('action' => 'view', \${$singularVar}['{$modelClass}']['{$primaryKey}']), array('escape' => false)); ?>\n";
	echo "\t\t\t<?php echo \$this->Html->link(\$this->Format->icon('edit'), array('action' => 'edit', \${$singularVar}['{$modelClass}']['{$primaryKey}']), array('escape' => false)); ?>\n";
	echo "\t\t\t<?php echo \$this->Form->postLink(\$this->Format->icon('delete'), array('action' => 'delete', \${$singularVar}['{$modelClass}']['{$primaryKey}']), array('escape' => false), __('Are you sure you want to delete # %s?', \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?>\n";

	echo "\t\t</td>\n";
	echo "\t</tr>\n";

	echo "<?php } ?>\n";
	?>
	</table>

	<div class="pagination-container">
<?php echo '<?php echo $this->element(\'Tools.pagination\'); ?>'; ?>

	</div>

</div>

<div class="actions">
	<ul>
		<li><?php echo "<?php echo \$this->Html->link(__('New %s', __('{$singularHumanName}')), array('action' => 'add')); ?>";?></li>
<?php
	$done = array();
	foreach ($associations as $type => $data) {
		// We dont need them
		break;
		foreach ($data as $alias => $details) {
			if ($details['controller'] !== $this->name && !in_array($details['controller'], $done)) {
				echo "\t\t<li><?php echo \$this->Html->link(__('List %s', __('" . Inflector::humanize($details['controller']) . "')), array('controller' => '{$details['controller']}', 'action' => 'index')); ?> </li>\n";
				$done[] = $details['controller'];
			}
		}
	}
?>
	</ul>
</div>