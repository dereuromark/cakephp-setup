<?php
/**
 * @var \App\View\AppView $this
 * @var array $tables
 */

use Cake\Utility\Inflector;

?>
<div class="columns col-md-12">

<h1><?php echo count($tables); ?> <?php echo __('DB Tables');?></h1>

<p>The following infos are just guesses based on CakePHP conventions.</p>

<?php

$snippets = [];
$checks = [];

foreach ($tables as $table => $data) {
	echo '<h2>' . h($table) . '</h2>';

	/** @var \Cake\Database\Schema\TableSchema $schema */
	$schema = $data['schema'];
	$columns = $schema->columns();

	echo '<p>' . count($schema->constraints()) . ' constraints</p>';
	echo '<ul>';
	foreach ($schema->constraints() as $constraint) {
		echo '<li><pre>' . print_r($schema->getConstraint($constraint), true) . '</pre></li>';
	}
	echo '</ul>';
	echo '<p>' . count($schema->indexes()) . ' indexes</p>';
	echo '<ul>';
	foreach ($schema->indexes() as $index) {
		echo '<li><pre>' . print_r($schema->getIndex($index), true) . '</pre></li>';
	}
	echo '</ul>';

	$keys = [];
	foreach ($columns as $column) {
		if (!preg_match('/_id$/', $column)) {
			continue;
		}
		$columnData = $schema->getColumn($column);
		// For now only int
		if ($columnData['type'] !== 'integer') {
			continue;
		}

		$constraints = [];
		$constraint = $schema->getConstraint($column);
		if ($constraint) {
			$constraints = [$constraint];
		}
		foreach ($schema->constraints() as $key) {
			$constraint = $schema->getConstraint($key);
			if (!in_array($column, $constraint['columns'], true)) {
				continue;
			}
			if ([$constraint] === $constraints) {
				continue;
			}

			$constraints[] = $constraint;
		}
		$columnData['constraints'] = $constraints;

		$indexes = [];
		$index = $schema->getIndex($column);
		if ($index) {
			$indexes = [$index];
		}
		foreach ($schema->indexes() as $key) {
			$index = $schema->getIndex($key);
			if (!in_array($column, $index['columns'], true)) {
				continue;
			}
			if ([$index] === $indexes) {
				continue;
			}

			$indexes[] = $index;
		}
		$columnData['indexes'] = $indexes;

		$keys[$column] = $columnData;
	}

	if (!$keys) {
		continue;
	}
?>

<table class="table list">
	<tr>
		<th>Column</th>
		<th>Constraints</th>
		<th>Indexes</th>
	</tr>
	<?php
foreach ($keys as $column => $columnData) {

	$nullable = $columnData['null'];

	if ($nullable)  {
		$output = $column . ' NULL';
	} else {
		$output = $column . ' NOT NULL';
	}

	$constraintWarning = null;

	if ($nullable) {
		$setNull = false;
		foreach ($columnData['constraints'] as $constraint) {
			if (!empty($constraint['delete']) && $constraint['delete'] === 'setNull') {
				$setNull = true;
				break;
			}
		}
		if (!$setNull) {
			$constraintWarning = 'Missing setNull on delete for nullable fk.';

			$target = Inflector::pluralize(mb_substr($column, 0, -3));
			$id = 'id';
			$snippets[] = <<<TXT
\$this->table('$table')
	->addForeignKey('$column', '$target', ['$id'], ['delete' => 'SET_NULL'])
	->update();
TXT;
			$checks[] = "SELECT * from $table LEFT JOIN $target ON ($table.$column = $target.$id) WHERE $table.$column IS NOT NULL AND $target.$id IS NULL;";
		}
	}

	if ($constraintWarning) {
		$constraintWarning = '<div class="inline-message warning">' . $constraintWarning . '</div>';
	}

	$indexWarning = null;

	echo '<tr>';
	echo '<td>' . h($output) . '</td>';
	echo '<td><pre>' . print_r($columnData['constraints'], true) . '</pre>' . $constraintWarning . '</td>';
	echo '<td><pre>' . print_r($columnData['indexes'], true) . '</pre>' . $indexWarning . '</td>';
	echo '</tr>';

}
?>
</table>

<?php
}
?>


	<?php if (!empty($snippets))
		echo '<h2>Suggested snippets for migrations</h2>';
		echo '<pre>' . implode(PHP_EOL, $snippets) . '</pre>';
	?>

	<?php if (!empty($checks))
		echo '<h2>Suggested checks before applying these migrations</h2>';
	echo '<p>You need to ensure existing rows are already clean of issues, these snippets will show you "invalid foreign keys":</p>';
	echo '<pre>' . implode(PHP_EOL, $checks) . '</pre>';
	?>

</div>
