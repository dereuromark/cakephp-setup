<%
use Cake\Core\Configure;
use Cake\Utility\Inflector;

$fields = collection($fields)
    ->filter(function($field) use ($schema) {
        return $schema->columnType($field) !== 'binary';
    });

if (isset($modelObject) && $modelObject->behaviors()->has('Tree')) {
    $fields = $fields->reject(function ($field) {
        return $field === 'lft' || $field === 'rght';
    });
}

$namespace = $plugin ?: Configure::read('App.namespace');

$skipFields = ['password', 'slug', 'created_by', 'modified_by', 'approved_by', 'deleted_by'];
if (property_exists($modelObject, 'scaffoldSkipFieldsForm')) {
    $skipFields = array_merge($skipFields, (array)$modelObject->scaffoldSkipFieldsForm);
}
if (property_exists($modelObject, 'scaffoldSkipFields')) {
    $skipFields = array_merge($skipFields, (array)$modelObject->scaffoldSkipFields);
}
%>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
    <ul class="side-nav nav nav-pills nav-stacked">
        <li class="heading"><?= __('Actions') ?></li>
<% if (strpos($action, 'add') === false): %>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $<%= $singularVar %>-><%= $primaryKey[0] %>],
                ['confirm' => __('Are you sure you want to delete # {0}?', $<%= $singularVar %>-><%= $primaryKey[0] %>)]
            )
        ?></li>
<% endif; %>
        <li><?= $this->Html->link(__('List <%= $pluralHumanName %>'), ['action' => 'index']) ?></li>
<%
        $done = [];
        foreach ($associations as $type => $data) {
            foreach ($data as $alias => $details) {
                if ($details['controller'] !== $this->name && !in_array($details['controller'], $done)) {
%>
        <li><?= $this->Html->link(__('List <%= $this->_pluralHumanName($alias) %>'), ['controller' => '<%= $details['controller'] %>', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New <%= $this->_singularHumanName($alias) %>'), ['controller' => '<%= $details['controller'] %>', 'action' => 'add']) ?></li>
<%
                    $done[] = $details['controller'];
                }
            }
        }
%>
    </ul>
</nav>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-xs-12">
    <?= $this->Form->create($<%= $singularVar %>) ?>
    <fieldset>
        <legend><?= __('<%= Inflector::humanize($action) %> <%= $singularHumanName %>') ?></legend>
        <?php
<%
        foreach ($fields as $field) {
            if (in_array($field, $primaryKey)) {
                continue;
            }
            if (in_array($field, $skipFields)) {
                continue;
            }

            if (isset($keyFields[$field])) {
                $fieldData = $schema->column($field);
                if (!empty($fieldData['null'])) {
%>
            echo $this->Form->control('<%= $field %>', ['options' => $<%= $keyFields[$field] %>, 'empty' => true]);
<%
                } else {
%>
            echo $this->Form->control('<%= $field %>', ['options' => $<%= $keyFields[$field] %>]);
<%
                }
                continue;
            }

            if (in_array($field, ['created', 'modified', 'updated'])) {
                continue;
            }

            $fieldType = $schema->columnType($field);
            $fieldData = $schema->column($field);
            if ($fieldType === 'integer' && $fieldData['length'] === 2 && method_exists($namespace . '\Model\Entity\\' . ($entityClass = ucfirst($singularVar)), $enumMethod = lcfirst(Inflector::camelize(Inflector::pluralize($field))))) {
                $empty = '';
                if (!empty($fieldData['null'])) {
                    $empty = ", 'empty' => true";
                }
%>
            echo $this->Form->control('<%= $field %>', ['options' => $<%= $singularVar %>::<%= $enumMethod %>()<%= $empty %>]);
<%
            } elseif (in_array($fieldData['type'], ['date', 'datetime', 'time']) && (!empty($fieldData['null']))) {
%>
            echo $this->Form->control('<%= $field %>', ['empty' => true]);
<%
            } else {
%>
            echo $this->Form->control('<%= $field %>');
<%
            }
        }
        if (!empty($associations['BelongsToMany'])) {
            foreach ($associations['BelongsToMany'] as $assocName => $assocData) {
%>
            echo $this->Form->control('<%= $assocData['property'] %>._ids', ['options' => $<%= $assocData['variable'] %>]);
<%
            }
        }
%>
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
