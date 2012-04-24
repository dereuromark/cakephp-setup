<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Console.Templates.default.views
 * @since         CakePHP(tm) v 1.2.0.5234
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<div class="page index">
	<h2><?php echo "<?php echo __('{$pluralHumanName}');?>";?></h2>

	<table class="list">
		<tr>
<?php
	if (App::import('Model', $plugin.'.'.$modelClass) || App::import('Model', $modelClass)) {
		$relationModel = new $modelClass;
	}
	$skipFields = array('id', 'password', 'slug', 'lft', 'rght', 'created_by', 'modified_by', 'approved_by', 'deleted_by');
	if (isset($relationModel) && property_exists($relationModel, 'scaffoldSkipFields')) {
		$skipFields = am($skipFields, (array)$relationModel->scaffoldSkipFields);
	}
?>
<?php foreach ($fields as $field):
	/** CORE-MOD - 2009-04-11 ms - no primaryKeys **/
	if (in_array($field, $skipFields) || (!empty($schema[$field]['key']) && $schema[$field]['key'] == 'primary') || ($field == 'sort' && $upDown)) {
		continue;
	}
	/** CORE-MOD END **/
?>
		<th><?php echo "<?php echo \$this->Paginator->sort('{$field}');?>";?></th>
<?php endforeach;?>
		<th class="actions"><?php echo "<?php echo __('Actions');?>";?></th>
	</tr>
<?php
	echo "<?php
\$i = 0;
foreach (\${$pluralVar} as \${$singularVar}): ?>\n";
	echo "\t<tr>\n";
	foreach ($fields as $field) {
		/** CORE-MOD (no id) **/
		if (in_array($field, $skipFields) || (!empty($schema[$field]['key']) && $schema[$field]['key'] == 'primary') || ($field == 'sort' && $upDown)) {
			continue;
		}
		/** CORE-MOD END **/
	
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
			/** CORE-MOD (datetime) **/
			if ($field == 'created' || $field == 'modified' || $schema[$field]['type'] == 'datetime') {
				echo "\t\t<td>\n\t\t\t<?php echo \$this->Datetime->niceDate(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";
			/** CORE-MOD END **/
	
			/** CORE-MOD (date) **/
			} elseif ($schema[$field]['type'] == 'date') {
				echo "\t\t<td>\n\t\t\t<?php echo \$this->Datetime->niceDate(\${$singularVar}['{$modelClass}']['{$field}'], FORMAT_NICE_YMD); ?>\n\t\t</td>\n";
			/** CORE-MOD END **/
	
			/** CORE-MOD (yes/no) **/
			} elseif ($schema[$field]['type'] == 'boolean') {
				echo "\t\t<td>\n\t\t\t<?php echo \$this->Format->yesNo(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";
			/** CORE-MOD END **/
	
			/** CORE-MOD (protection against js injection by using h() function) **/
			/*
			} elseif (strlen($field) > 3 && substr($field, -3, 3)=='_id') {
				# "unchanged" output?
				// echo "\t\t<td>\n\t\t\t<?php echo \${$singularVar}['{$modelClass}']['{$field}']; ?>\n\t\t</td>\n";
				# no difference to normal output right now...
				echo "\t\t<td>\n\t\t\t<?php echo h(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";
			*/
			
			/** CORE-MOD (nl2br + h) **/
			} elseif ($schema[$field]['type'] == 'text') {
				# "unchanged" output?
				/* echo "\t\t<td>\n\t\t\t<?php echo \${$singularVar}['{$modelClass}']['{$field}']; ?>\n\t\t</td>\n"; */
				# no difference to normal output right now...
				echo "\t\t<td>\n\t\t\t<?php echo nl2br(h(\${$singularVar}['{$modelClass}']['{$field}'])); ?>\n\t\t</td>\n";
	
			} elseif ($schema[$field]['type'] == 'integer' && method_exists($modelClass, $enumMethod = lcfirst(Inflector::camelize(Inflector::pluralize($field))))) {
				echo "\t\t<td>\n\t\t\t<?php echo ".$modelClass."::".$enumMethod."(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";
	
			} elseif ($schema[$field]['type'] == 'float' && strpos($schema[$field]['length'], ',') !== false) {
				echo "\t\t<td>\n\t\t\t<?php echo \$this->Numeric->money(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";
	
			} else {
				//$schema[$field]['type'] == 'string'
				# escape: h()
				echo "\t\t<td>\n\t\t\t<?php echo h(\${$singularVar}['{$modelClass}']['{$field}']); ?>\n\t\t</td>\n";
			}
			/** CORE-MOD END **/
		}
	}
	
		echo "\t\t<td class=\"actions\">\n";
	
	/** CORE-MOD **/
	if (!empty($upDown)) {
		echo "\t\t\t<?php echo \$this->Html->link(\$this->Format->icon('up'), array('action'=>'up', \${$singularVar}['{$modelClass}']['{$primaryKey}']), array('escape'=>false)); ?>\n";
		echo "\t\t\t<?php echo \$this->Html->link(\$this->Format->icon('down'), array('action'=>'down', \${$singularVar}['{$modelClass}']['{$primaryKey}']), array('escape'=>false)); ?>\n";
	}
	echo "\t\t\t<?php echo \$this->Html->link(\$this->Format->icon('view'), array('action'=>'view', \${$singularVar}['{$modelClass}']['{$primaryKey}']), array('escape'=>false)); ?>\n";
	echo "\t\t\t<?php echo \$this->Html->link(\$this->Format->icon('edit'), array('action'=>'edit', \${$singularVar}['{$modelClass}']['{$primaryKey}']), array('escape'=>false)); ?>\n";
	echo "\t\t\t<?php echo \$this->Form->postLink(\$this->Format->icon('delete'), array('action'=>'delete', \${$singularVar}['{$modelClass}']['{$primaryKey}']), array('escape'=>false), __('Are you sure you want to delete # %s?', \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?>\n";
	/** CORE-MOD END **/
		echo "\t\t</td>\n";
	echo "\t</tr>\n";

	echo "<?php endforeach; ?>\n";
	?>
	</table>

	<p class="pagination">
<?php echo '<?php echo $this->element(\'pagination\', array(), array(\'plugin\'=>\'tools\')); ?>'; ?>

	</p>

</div>

<div class="actions">
<?php
	/*<h3><?php echo "<?php echo __('Actions'); ?>"; ?></h3>*/
?>
	<ul>
		<li><?php echo "<?php echo \$this->Html->link(__('New %s', __('{$singularHumanName}')), array('action' => 'add')); ?>";?></li>
<?php
	$done = array();
	foreach ($associations as $type => $data) {
		foreach ($data as $alias => $details) {
			if ($details['controller'] != $this->name && !in_array($details['controller'], $done)) {
				echo "\t\t<li><?php echo \$this->Html->link(__('List %s', __('" . Inflector::humanize($details['controller']) . "')), array('controller' => '{$details['controller']}', 'action' => 'index')); ?> </li>\n";
				/*
				echo "\t\t<li><?php echo \$this->Html->link(__('New %s', __('" . Inflector::humanize(Inflector::underscore($alias)) . "')), array('controller' => '{$details['controller']}', 'action' => 'add')); ?> </li>\n";
				*/
				$done[] = $details['controller'];
			}
		}
	}
?>
	</ul>
</div>