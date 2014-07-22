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
<div class="page view">
<h2><?php echo "<?php  echo __('{$singularHumanName}');?>";?></h2>
	<dl>
<?php
if (App::import('Model', $plugin . '.' . $modelClass) || App::import('Model', $modelClass)) {
	$relationModel = new $modelClass;
}
$skipFields = array('id', 'password', 'slug', 'lft', 'rght', 'created_by', 'modified_by', 'approved_by', 'deleted_by');
if (isset($relationModel) && property_exists($relationModel, 'scaffoldSkipFields')) {
	$skipFields = array_merge($skipFields, (array)$relationModel->scaffoldSkipFields);
}

foreach ($fields as $field) {
	// Prevents id fields to be displayed (not needed!)
	if (in_array($field, $skipFields) || (!empty($schema[$field]['key']) && $schema[$field]['key'] === 'primary') || ($field === 'sort' && $upDown)) {
		continue;
	}

	$isKey = false;
	if (!empty($associations['belongsTo'])) {
		foreach ($associations['belongsTo'] as $alias => $details) {
			if ($field === $details['foreignKey']) {
				$isKey = true;
				echo "\t\t<dt><?php echo __('" . Inflector::humanize(Inflector::underscore($alias)) . "'); ?></dt>\n";
				echo "\t\t<dd>\n\t\t\t<?php echo \$this->Html->link(\${$singularVar}['{$alias}']['{$details['displayField']}'], array('controller' => '{$details['controller']}', 'action' => 'view', \${$singularVar}['{$alias}']['{$details['primaryKey']}'])); ?>\n\t\t\t&nbsp;\n\t\t</dd>\n";
				break;
			}
		}
	}
	if ($isKey !== true) {
		if ($field === 'modified' && !empty($fieldCreated)) {
			echo "<?php if (\${$singularVar}['{$modelClass}']['created'] !== \${$singularVar}['{$modelClass}']['{$field}']) { ?>\n";
		}

		echo "\t\t<dt><?php echo __('" . Inflector::humanize($field) . "'); ?></dt>\n";

		if ($field === 'created' || $field === 'modified' || $schema[$field]['type'] === 'datetime') {
			// Localize date/time output
			if ($field === 'created') {
				$fieldCreated = true;
			}

			echo "\t\t<dd>\n\t\t\t<?php echo ";
			echo "\$this->Datetime->niceDate(\${$singularVar}['{$modelClass}']['{$field}'])";
			echo "; ?>\n\t\t\t&nbsp;\n\t\t</dd>\n";

			if ($field === 'modified' && !empty($fieldCreated)) {
				echo "<?php } ?>\n";
			}

		} elseif ($schema[$field]['type'] === 'date') {
			// Localize date only output
			echo "\t\t<dd>\n\t\t\t<?php echo ";
			echo "\$this->Datetime->niceDate(\${$singularVar}['{$modelClass}']['{$field}'], FORMAT_NICE_YMD)";
			echo "; ?>\n\t\t\t&nbsp;\n\t\t</dd>\n";

		} elseif ($schema[$field]['type'] === 'boolean') {
			// Boolean Yes/No images
			echo "\t\t<dd>\n\t\t\t<?php echo \$this->Format->yesNo(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t\t&nbsp;\n\t\t</dd>\n"; //display an icon (green yes / red no)

		} elseif ($schema[$field]['type'] === 'text') {
			// Newlines in textareas
			echo "\t\t<dd>\n\t\t\t<?php echo nl2br(h(\${$singularVar}['{$modelClass}']['{$field}'])); ?>\n\t\t\t&nbsp;\n\t\t</dd>\n";

		} elseif ($schema[$field]['type'] === 'integer' && method_exists($modelClass, $enumMethod = lcfirst(Inflector::camelize(Inflector::pluralize($field))))) {
			// Handle Tools "enums"
			echo "\t\t<dd>\n\t\t\t<?php echo " . Inflector::camelize($modelClass) . "::" . $enumMethod . "(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t\t&nbsp;\n\t\t</dd>\n";

		} elseif ($schema[$field]['type'] === 'decimal' || $schema[$field]['type'] === 'float' && strpos($schema[$field]['length'], ',2') !== false) {
			// Money formatting
			echo "\t\t<td>\n\t\t\t<?php echo \$this->Numeric->money(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";

		} elseif ($schema[$field]['type'] === 'float' && strpos($schema[$field]['length'], ',') !== false) {
			// Generic float value handling
			echo "\t\t<td>\n\t\t\t<?php echo \$this->Numeric->format(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";

		} else {
			// Protection against js injection by using h() function)
			echo "\t\t<dd>\n\t\t\t<?php echo h(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t\t&nbsp;\n\t\t</dd>\n";
		}
	}
}
?>
	</dl>
</div>

<div class="actions">
	<ul>
<?php
	echo "\t\t<li><?php echo \$this->Html->link(__('Edit %s', __('{$singularHumanName}')), array('action' => 'edit', \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?> </li>\n";
	echo "\t\t<li><?php echo \$this->Form->postLink(__('Delete %s', __('{$singularHumanName}')), array('action' => 'delete', \${$singularVar}['{$modelClass}']['{$primaryKey}']), array(), __('Are you sure you want to delete # %s?', \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?> </li>\n";
	echo "\t\t<li><?php echo \$this->Html->link(__('List %s', __('{$pluralHumanName}')), array('action' => 'index')); ?> </li>\n";

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
<?php
if (!empty($associations['hasOne'])) :
	foreach ($associations['hasOne'] as $alias => $details): ?>
	<div class="related">
		<h3><?php echo "<?php echo __('Related %s', __('" . Inflector::humanize($details['controller']) . "'));?>";?></h3>
	<?php echo "<?php if (!empty(\${$singularVar}['{$alias}'])):?>\n";?>
		<dl><?php echo "\t<?php \$i = 0; \$class = ' class=\"altrow\"';?>\n";?>
	<?php
			foreach ($details['fields'] as $field) {
				echo "\t\t<dt><?php echo __('" . Inflector::humanize($field) . "');?></dt>\n";
				echo "\t\t<dd>\n\t<?php echo \${$singularVar}['{$alias}']['{$field}'];?>\n&nbsp;</dd>\n";
			}
	?>
		</dl>
	<?php echo "<?php endif; ?>\n";?>
		<div class="actions">
			<ul>
				<li><?php echo "<?php echo \$this->Html->link(__('Edit %s', __('" . Inflector::humanize(Inflector::underscore($alias)) . "')), array('controller' => '{$details['controller']}', 'action' => 'edit', \${$singularVar}['{$alias}']['{$details['primaryKey']}'])); ?></li>\n";?>
			</ul>
		</div>
	</div>
	<?php
	endforeach;
endif;

if (empty($associations['hasMany'])) {
	$associations['hasMany'] = array();
}
if (empty($associations['hasAndBelongsToMany'])) {
	$associations['hasAndBelongsToMany'] = array();
}
$relations = array_merge($associations['hasMany'], $associations['hasAndBelongsToMany']);

foreach ($relations as $alias => $details) {
	// We dont need them
	break;

	$otherSingularVar = Inflector::variable($alias);
	$otherPluralHumanName = Inflector::humanize($details['controller']);
	?>
<div class="related">
	<h3><?php echo "<?php echo __('Related %s', __('{$otherPluralHumanName}'));?>";?></h3>
	<?php echo "<?php if (!empty(\${$singularVar}['{$alias}'])):?>\n";?>
	<table class="list">
	<tr>
<?php
			foreach ($details['fields'] as $field) {
				echo "\t\t<th><?php echo __('" . Inflector::humanize($field) . "'); ?></th>\n";
			}
?>
		<th class="actions"><?php echo "<?php echo __('Actions');?>";?></th>
	</tr>
<?php
echo "\t<?php
		foreach (\${$singularVar}['{$alias}'] as \${$otherSingularVar}) { ?>\n";
			echo "\t\t<tr>\n";

			foreach ($details['fields'] as $field) {
				echo "\t\t\t<td><?php echo \${$otherSingularVar}['{$field}'];?></td>\n";
			}

			echo "\t\t\t<td class=\"actions\">\n";
			echo "\t\t\t\t<?php echo \$this->Html->link(__('View'), array('controller' => '{$details['controller']}', 'action' => 'view', \${$otherSingularVar}['{$details['primaryKey']}'])); ?>\n";
			echo "\t\t\t\t<?php echo \$this->Html->link(__('Edit'), array('controller' => '{$details['controller']}', 'action' => 'edit', \${$otherSingularVar}['{$details['primaryKey']}'])); ?>\n";
			echo "\t\t\t\t<?php echo \$this->Form->postLink(__('Delete'), array('controller' => '{$details['controller']}', 'action' => 'delete', \${$otherSingularVar}['{$details['primaryKey']}']), array(), __('Are you sure you want to delete # %s?', \${$otherSingularVar}['{$details['primaryKey']}'])); ?>\n";
			echo "\t\t\t</td>\n";
			echo "\t\t</tr>\n";

echo "\t<?php } ?>\n";
?>
	</table>
<?php echo "<?php endif; ?>\n\n";?>
	<div class="actions">
		<ul>
			<li><?php echo "<?php echo \$this->Html->link(__('Add %s', __('" . Inflector::humanize(Inflector::underscore($alias)) . "')), array('controller' => '{$details['controller']}', 'action' => 'add'));?>"; /** CORE-MOD **/ ?> </li>
		</ul>
	</div>
</div>
<?php } ;?>