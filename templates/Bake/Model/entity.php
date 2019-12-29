<%
$propertyHintMap = $this->DocBlock->buildEntityPropertyHintTypeMap(isset($propertySchema) ? $propertySchema : []);
$associationHintMap = $this->DocBlock->buildEntityAssociationHintTypeMap(isset($propertySchema) ? $propertySchema : []);

$annotations = $this->DocBlock->propertyHints($propertyHintMap);
if(!empty($associationHintMap)) {
    $annotations[] = "";
    $annotations = array_merge($annotations, $this->DocBlock->propertyHints($associationHintMap));
}

$accessible = [];
if (!isset($fields) || $fields !== false) {
    if (!empty($fields)) {
        foreach ($fields as $field) {
            $accessible[$field] = 'true';
        }
    } elseif (!empty($primaryKey)) {
        $accessible['*'] = 'true';
        foreach ($primaryKey as $field) {
            $accessible[$field] = 'false';
        }
    }
}

$entityNamespace = $namespace . '\Model\Entity\\';

$entityClass = 'Tools\Model\Entity\Entity';
if (class_exists($entityNamespace . 'Entity')) {
    $entityClass = $entityNamespace . 'Entity';
}
%>
<?php
namespace <%= $namespace %>\Model\Entity;

use <%= $entityClass %>;

<%= $this->DocBlock->classDescription($name, 'Entity', $annotations) %>
class <%= $name %> extends Entity
{
<% if (!empty($accessible)): %>

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
<% foreach ($accessible as $field => $value): %>
        '<%= $field %>' => <%= $value %>,
<% endforeach; %>
    ];
<% endif %>
<% if (!empty($hidden)): %>

    /**
     * Fields that are excluded from JSON an array versions of the entity.
     *
     * @var array
     */
    protected $_hidden = [<%= $this->Bake->stringifyList($hidden, ['trailingComma' => true]) %>];
<% endif %>
<% if (empty($accessible) && empty($hidden)): %>

<% endif %>
}
