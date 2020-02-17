<%
use Cake\Utility\Inflector;

$annotations = [];
foreach ($associations as $type => $assocs) {
    foreach ($assocs as $assoc) {
        $typeStr = Inflector::camelize($type);
        $tableFqn = $associationInfo[$assoc['alias']]['targetFqn'];
        $annotations[] = "@property {$tableFqn}&\Cake\ORM\Association\\{$typeStr} \${$assoc['alias']}";
    }
}
$annotations[] = "@method \\{$namespace}\\Model\\Entity\\{$entity} newEmptyEntity()";
$annotations[] = "@method \\{$namespace}\\Model\\Entity\\{$entity} newEntity(\$data = null, array \$options = [])";
$annotations[] = "@method \\{$namespace}\\Model\\Entity\\{$entity}[] newEntities(array \$data, array \$options = [])";
$annotations[] = "@method \\{$namespace}\\Model\\Entity\\{$entity} findOrCreate(\$search, callable \$callback = null, \$options = [])";
$annotations[] = "@method \\{$namespace}\\Model\\Entity\\{$entity} get(\$primaryKey, \$options = [])";
$annotations[] = "@method \\{$namespace}\\Model\\Entity\\{$entity} patchEntity(\\Cake\\Datasource\\EntityInterface \$entity, array \$data, array \$options = [])";
$annotations[] = "@method \\{$namespace}\\Model\\Entity\\{$entity}[] patchEntities(\$entities, array \$data, array \$options = [])";
$annotations[] = "@method \\{$namespace}\\Model\\Entity\\{$entity}|false save(\\Cake\\Datasource\\EntityInterface \$entity, \$options = [])";
$annotations[] = "@method \\{$namespace}\\Model\\Entity\\{$entity} saveOrFail(\\Cake\\Datasource\\EntityInterface \$entity, \$options = [])";
$annotations[] = "@method \\{$namespace}\\Model\\Entity\\{$entity}[]|\Cake\Datasource\ResultSetInterface|false saveMany(\$entities, \$options = [])";
foreach ($behaviors as $behavior => $behaviorData) {
    $annotations[] = "@mixin \Cake\ORM\Behavior\\{$behavior}Behavior";
}
%>
<?php

namespace <%= $namespace %>\Model\Table;

<%

$tableNamespace = $namespace . '\Model\Table';

$tableClass = 'Tools\Model\Table\Table';
if (class_exists($tableNamespace . '\Table')) {
    $tableClass = $tableNamespace . '\Table';
}

$uses = [
    'use Cake\ORM\RulesChecker;',
    'use Cake\Validation\Validator;'
];
if ($tableClass !== 'App\Model\Table\Table') {
    $uses[] = 'use ' . $tableClass . ';';
}
sort($uses);
echo implode("\n", $uses);
%>


<%= $this->DocBlock->classDescription($name, 'Model', $annotations) %>
class <%= $name %>Table extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

<% if (!empty($table)): %>
        $this->setTable('<%= $table %>');
<% endif %>
<% if (!empty($displayField)): %>
        $this->setDisplayField('<%= $displayField %>');
<% endif %>
<% if (!empty($primaryKey)): %>
<% if (count($primaryKey) > 1): %>
        $this->setPrimaryKey([<%= $this->Bake->stringifyList((array)$primaryKey, ['indent' => false]) %>]);
<% else: %>
        $this->setPrimaryKey('<%= current((array)$primaryKey) %>');
<% endif %>
<% endif %>
<% if (!empty($behaviors)): %>

<% endif; %>
<% foreach ($behaviors as $behavior => $behaviorData): %>
        $this->addBehavior('<%= $behavior %>'<%= $behaviorData ? ", [" . implode(', ', $behaviorData) . ']' : '' %>);
<% endforeach %>
<% if (!empty($associations)): %>

<% endif; %>
<% foreach ($associations as $type => $assocs): %>
<% foreach ($assocs as $assoc):
    $alias = $assoc['alias'];
    unset($assoc['alias']);
%>
        $this-><%= $type %>('<%= $alias %>', [<%= $this->Bake->stringifyList($assoc, ['indent' => 3, 'trailingComma' => true]) %>]);
<% endforeach %>
<% endforeach %>
    }
<% if (!empty($validation)): %>

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
<%
foreach ($validation as $field => $rules):
    $validationMethods = $this->Bake->getValidationMethods($field, $rules);

    if (!empty($validationMethods)):
        $lastIndex = count($validationMethods) - 1;
        $validationMethods[$lastIndex] .= ';';
        %>
        $validator
        <%- foreach ($validationMethods as $validationMethod): %>
            <%= $validationMethod %>
        <%- endforeach; %>

<%
    endif;
endforeach;
%>
        return $validator;
    }
<% endif %>
<% if (!empty($rulesChecker)): %>

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
    <%- foreach ($rulesChecker as $field => $rule): %>
        $rules->add($rules-><%= $rule['name'] %>(['<%= $field %>']<%= !empty($rule['extra']) ? ", '$rule[extra]'" : '' %>));
    <%- endforeach; %>
        return $rules;
    }
<% endif; %>
<% if ($connection !== 'default'): %>

    /**
     * Returns the database connection name to use by default.
     *
     * @return string
     */
    public static function defaultConnectionName()
    {
        return '<%= $connection %>';
    }
<% endif; %>
}
