<?php

namespace Setup\View\Helper;

use Bake\View\Helper\BakeHelper;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\SchemaInterface;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class SetupBakeHelper extends BakeHelper {

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
		if (!in_array($type, ['integer', 'tinyinteger', 'biginteger', 'smallinteger', 'tinyinteger'])) {
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

		$fieldData = $schema->getColumn($field);
		if (in_array($field, $paginationOrderReversedFields, true) || in_array($fieldData['type'], $paginationOrderReversedFieldTypes, true)) {
			return true;
		}

		return false;
	}

	/**
	 * Get field accessibility data.
	 *
	 * @param string[]|false|null $fields Fields list.
	 * @param string[]|null $primaryKey Primary key.
	 * @return string[]
	 */
	public function getFieldAccessibility($fields = null, $primaryKey = null): array {
		$accessibleFields = parent::getFieldAccessibility($fields, $primaryKey);

		$accessible['*'] = $accessibleFields['*'] ?? 'true';
		foreach ((array)$primaryKey as $field) {
			$accessible[$field] = 'false';
		}
		foreach ($accessibleFields as $field => $status) {
			if ($status === false) {
				$accessible[$field] = 'false';
			}
		}

		return $accessible;
	}

	/**
	 * Return list of fields to generate controls for.
	 *
	 * @param array $fields Fields list.
	 * @param \Cake\Datasource\SchemaInterface $schema Schema instance.
	 * @param \Cake\ORM\Table|null $modelObject Model object.
	 * @param string|int $takeFields Take fields.
	 * @param array $filterTypes Filter field types.
	 * @param string|null $type
	 * @return array
	 */
	public function filterFields(
		array $fields,
		SchemaInterface $schema,
		?Table $modelObject = null,
		$takeFields = 0,
		$filterTypes = ['binary'],
		?string $type = null
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
		if ($type && $modelObject && property_exists($modelObject, $customProperty)) {
			$skipFields = array_merge($skipFields, (array)$modelObject->$customProperty);
		}
		if ($modelObject && property_exists($modelObject, 'scaffoldSkipFields')) {
			$skipFields = array_merge($skipFields, (array)$modelObject->scaffoldSkipFields);
		}

		$fields = $fields->reject(function ($field) use ($skipFields) {
			return in_array($field, $skipFields, true);
		});

		return $fields->toArray();
	}

}
