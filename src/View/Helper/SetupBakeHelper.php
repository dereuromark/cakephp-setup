<?php

namespace Setup\View\Helper;

use Bake\View\Helper\BakeHelper;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\SchemaInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class SetupBakeHelper extends BakeHelper {

	/**
	 * @param string $field
	 * @param \Cake\Database\Schema\TableSchemaInterface $schema
	 *
	 * @return bool
	 */
	public function isDateTime(string $field, TableSchemaInterface $schema): bool {
		$type = $schema->getColumnType($field);

		return in_array($type, ['date', 'time', 'datetime', 'timestamp'], true);
	}

	/**
	 * @param string $field
	 * @param \Cake\Database\Schema\TableSchemaInterface $schema
	 *
	 * @return bool
	 */
	public function isText(string $field, TableSchemaInterface $schema): bool {
		$type = $schema->getColumnType($field);

		return in_array($type, ['text', 'mediumtext', 'longtext'], true);
	}

	/**
	 * @param string $field
	 * @param \Cake\Database\Schema\TableSchemaInterface $schema
	 *
	 * @return bool
	 */
	public function isArray(string $field, TableSchemaInterface $schema): bool {
		$type = $schema->getColumnType($field);

		return in_array($type, ['array', 'json'], true);
	}

	/**
	 * @param string $field
	 * @param \Cake\Database\Schema\TableSchemaInterface $schema
	 * @param string $singularVar
	 * @param string $namespace
	 *
	 * @return bool
	 */
	public function isEnum(string $field, TableSchemaInterface $schema, string $singularVar, string $namespace): bool {
		$type = $schema->getColumnType($field);
		if (!in_array($type, ['integer', 'tinyinteger', 'biginteger', 'smallinteger', 'tinyinteger'], true)) {
			return false;
		}

		$entityClassName = ucfirst($singularVar);
		$enumMethod = $this->enumMethod($field);

		return method_exists($namespace . '\Model\Entity\\' . $entityClassName, $enumMethod);
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	public function enumMethod(string $field): string {
		return lcfirst(Inflector::camelize(Inflector::pluralize($field)));
	}

	/**
	 * We want certain fields to be paginated DESC by default, also certain types
	 *
	 * @param string $field
	 * @param \Cake\Database\Schema\TableSchemaInterface $schema
	 * @param \Cake\ORM\Table|null $modelObject
	 *
	 * @return bool
	 */
	public function isPaginationOrderReversed(string $field, TableSchemaInterface $schema, ?Table $modelObject): bool {
		$paginationOrderReversedFields = ['published', 'rating', 'priority'];
		if ($modelObject && property_exists($modelObject, 'paginationOrderReversedFields')) {
			$paginationOrderReversedFields = array_merge($paginationOrderReversedFields, (array)$modelObject->paginationOrderReversedFields);
		}
		$paginationOrderReversedFieldTypes = ['datetime', 'date', 'time', 'bool'];
		if ($modelObject && property_exists($modelObject, 'paginationOrderReversedFieldTypes')) {
			$paginationOrderReversedFieldTypes = array_merge($paginationOrderReversedFieldTypes, (array)$modelObject->paginationOrderReversedFieldTypes);
		}

		$fieldData = (array)$schema->getColumn($field);
		if (in_array($field, $paginationOrderReversedFields, true) || in_array($fieldData['type'], $paginationOrderReversedFieldTypes, true)) {
			return true;
		}

		return false;
	}

	/**
	 * Get field accessibility data.
	 *
	 * @param array<string>|false|null $fields Fields list.
	 * @param array<string>|null $primaryKey Primary key.
	 * @return array<string, bool>
	 */
	public function getFieldAccessibility($fields = null, $primaryKey = null): array {
		$accessibleFields = parent::getFieldAccessibility($fields, $primaryKey);

		$accessible = [];
		$accessible['*'] = $accessibleFields['*'] ?? true;
		foreach ((array)$primaryKey as $field) {
			$accessible[$field] = false;
		}
		foreach ($accessibleFields as $field => $status) {
			if (!$status) {
				$accessible[$field] = false;
			}
		}

		return $accessible;
	}

	/**
	 * Get fields data for view template.
	 *
	 * @param array $fields Fields list.
	 * @param \Cake\Datasource\SchemaInterface $schema Schema instance.
	 * @param array $associations Associations data.
	 * @param \Cake\ORM\Table|null $modelObject
	 * @return array
	 */
	public function getViewFieldsData(array $fields, SchemaInterface $schema, array $associations, ?Table $modelObject = null): array {
		$fields = $this->filterFields($fields, $schema, $modelObject, 0, [], 'view');

		return parent::getViewFieldsData($fields, $schema, $associations);
	}

	/**
	 * Return list of fields to generate controls for.
	 *
	 * @param array $fields Fields list.
	 * @param \Cake\Datasource\SchemaInterface $schema Schema instance.
	 * @param \Cake\ORM\Table|null $modelObject Model object.
	 * @param string|int $takeFields Take fields.
	 * @param array<string> $filterTypes Filter field types.
	 * @param string|null $type
	 * @return array
	 */
	public function filterFields(
		array $fields,
		SchemaInterface $schema,
		?Table $modelObject = null,
		string|int $takeFields = 0,
		array $filterTypes = ['binary'],
		?string $type = null,
	): array {
		$fields = parent::filterFields($fields, $schema, $modelObject, $takeFields, $filterTypes);

		$fields = collection($fields);
		if (isset($modelObject) && $modelObject->behaviors()->has('Tree')) {
			$fields = $fields->reject(function ($field) {
				return $field === 'lft' || $field === 'rght';
			});
		}

		$skipFields = ['password', 'slug', 'created_by', 'modified_by', 'approved_by', 'deleted_by'];
		$customProperty = $type ? 'scaffoldSkipFields' . ucfirst($type) : null;
		if ($type && $customProperty && $modelObject && property_exists($modelObject, $customProperty)) {
			$skipFields = array_merge($skipFields, (array)$modelObject->$customProperty);
		}
		if ($modelObject && property_exists($modelObject, 'scaffoldSkipFields')) {
			$skipFields = array_merge($skipFields, (array)$modelObject->scaffoldSkipFields);
		}

		$fields = $fields->reject(function ($field) use ($skipFields) {
			return in_array($field, $skipFields, true);
		});

		/** @var \Cake\Collection\Collection $fields */
		return $fields->toArray();
	}

	/**
	 * @param string $currentModelName
	 * @return array<string, mixed>
	 */
	public function pagination(string $currentModelName): array {
		try {
			$model = TableRegistry::getTableLocator()->get($currentModelName);
			$tableSchema = $model->getSchema();

			$fields = ['published', 'created', 'modified'];
			foreach ($fields as $field) {
				if ($tableSchema->hasColumn($field)) {
					return ['order' => [$currentModelName . '.created' => 'DESC']];
				}
			}
		} catch (\Throwable) {
			// ignore
		}

		return [];
	}

	/**
	 * @param string $records
	 * @return string
	 */
	public function fixtureRecords(string $records): string {
		return preg_replace("/\s*'id'\s*=>\s*\d+,/", '', $records);
	}

}
