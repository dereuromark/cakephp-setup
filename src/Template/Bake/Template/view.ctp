<?php
/**
 * @var \<%= $namespace %>\View\AppView $this
 * @var \<%= $entityClass %> $<%= $singularVar %>
 */
?>
<%
use Cake\Core\Configure;
use Cake\Utility\Inflector;

$associations += ['BelongsTo' => [], 'HasOne' => [], 'HasMany' => [], 'BelongsToMany' => []];
$immediateAssociations = $associations['BelongsTo'] + $associations['HasOne'];
$associationFields = collection($fields)
    ->map(function($field) use ($immediateAssociations) {
        foreach ($immediateAssociations as $alias => $details) {
            if ($field === $details['foreignKey']) {
                return [$field => $details];
            }
        }
    })
    ->filter()
    ->reduce(function($fields, $value) {
        return $fields + $value;
    }, []);

$namespace = Configure::read('App.namespace');

$skipFields = ['password', 'slug', 'created_by', 'modified_by', 'approved_by', 'deleted_by'];
if (property_exists($modelObject, 'scaffoldSkipFieldsView')) {
    $skipFields = array_merge($skipFields, (array)$modelObject->scaffoldSkipFieldsView);
}
if (property_exists($modelObject, 'scaffoldSkipFields')) {
    $skipFields = array_merge($skipFields, (array)$modelObject->scaffoldSkipFields);
}

$groupedFields = collection($fields)
    ->filter(function($field) use ($schema, $skipFields) {
        $primaryKeys = $schema->primaryKey();
        if (in_array($field, $primaryKeys)) {
            return false;
        }

        if (in_array($field, $skipFields)) {
            return false;
        }

        return $schema->columnType($field) !== 'binary';
    })
    ->groupBy(function($field) use ($schema, $associationFields) {
        $type = $schema->columnType($field);
        return in_array($type, ['text']) ? $type : 'default';
    })
    ->toArray();

$groupedFields += ['number' => [], 'string' => [], 'boolean' => [], 'date' => [], 'text' => []];
$pk = "\$$singularVar->{$primaryKey[0]}";
%>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12">
    <ul class="side-nav nav nav-pills nav-stacked">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit <%= $singularHumanName %>'), ['action' => 'edit', <%= $pk %>]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete <%= $singularHumanName %>'), ['action' => 'delete', <%= $pk %>], ['confirm' => __('Are you sure you want to delete # {0}?', <%= $pk %>)]) ?> </li>
        <li><?= $this->Html->link(__('List <%= $pluralHumanName %>'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New <%= $singularHumanName %>'), ['action' => 'add']) ?> </li>
<%
    $done = [];
    foreach ($associations as $type => $data) {
        foreach ($data as $alias => $details) {
            if ($details['controller'] !== $this->name && !in_array($details['controller'], $done)) {
%>
        <li><?= $this->Html->link(__('List <%= $this->_pluralHumanName($alias) %>'), ['controller' => '<%= $details['controller'] %>', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New <%= Inflector::humanize(Inflector::singularize(Inflector::underscore($alias))) %>'), ['controller' => '<%= $details['controller'] %>', 'action' => 'add']) ?> </li>
<%
                $done[] = $details['controller'];
            }
        }
    }
%>
    </ul>
</nav>
<div class="action-view large-9 medium-8 columns col-sm-8 col-xs-12">
    <h2><?= h($<%= $singularVar %>-><%= $displayField %>) ?></h2>
    <table class="table vertical-table">
<% if ($groupedFields['default']) : %>
<% foreach ($groupedFields['default'] as $field) : %>
<% $fieldType = $schema->columnType($field); %>
<% if (isset($associationFields[$field])) :
            $details = $associationFields[$field];
%>
        <tr>
            <th><?= __('<%= Inflector::humanize($details['property']) %>') ?></th>
            <td><?= $<%= $singularVar %>->has('<%= $details['property'] %>') ? $this->Html->link($<%= $singularVar %>-><%= $details['property'] %>-><%= $details['displayField'] %>, ['controller' => '<%= $details['controller'] %>', 'action' => 'view', $<%= $singularVar %>-><%= $details['property'] %>-><%= $details['primaryKey'][0] %>]) : '' ?></td>
        </tr>
<% else : %>
        <tr>
            <th><?= __('<%= Inflector::humanize($field) %>') ?></th>
<% if (in_array($fieldType, ['integer']) && method_exists($namespace . '\Model\Entity\\' . ($entityClass = ucfirst($singularVar)), $enumMethod = lcfirst(Inflector::camelize(Inflector::pluralize($field))))): %>
            <td><?= $<%= $singularVar %>::<%= $enumMethod%>($<%= $singularVar %>-><%= $field %>) ?></td>
<% elseif (in_array($fieldType, ['integer', 'float', 'decimal', 'biginteger'])): %>
            <td><?= $this->Number->format($<%= $singularVar %>-><%= $field %>) ?></td>
<% elseif (in_array($fieldType, ['date', 'time', 'datetime', 'timestamp'])): %>
            <td><?= $this->Time->nice($<%= $singularVar %>-><%= $field %>) ?></td>
<% elseif (in_array($fieldType, ['boolean'])): %>
            <td><?= $this->Format->yesNo($<%= $singularVar %>-><%= $field %>) ?></td>
<% else : %>
            <td><?= h($<%= $singularVar %>-><%= $field %>) ?></td>
<% endif; %>
        </tr>
<% endif; %>
<% endforeach; %>
<% endif; %>
    </table>
<% if ($groupedFields['text']) : %>
<% foreach ($groupedFields['text'] as $field) : %>
    <div class="row">
        <h3><?= __('<%= Inflector::humanize($field) %>') ?></h3>
        <?= $this->Text->autoParagraph(h($<%= $singularVar %>-><%= $field %>)); ?>
    </div>
<% endforeach; %>
<% endif; %>

<%
$relations = $associations['HasMany'] + $associations['BelongsToMany'];
foreach ($relations as $alias => $details):
    $otherSingularVar = Inflector::variable($alias);
    $otherPluralHumanName = Inflector::humanize(Inflector::underscore($details['controller']));
    %>
    <div class="related">
        <h3><?= __('Related <%= $otherPluralHumanName %>') ?></h3>
        <?php if (!empty($<%= $singularVar %>-><%= $details['property'] %>)): ?>
        <table class="table">
            <tr>
<% foreach ($details['fields'] as $field): %>
            <%
            $primaryKeys = $schema->primaryKey();
            if (in_array($field, $primaryKeys)) {
                continue;
            }

            if (in_array($field, $skipFields)) {
                continue;
            }
            %>
            <th><?= __('<%= Inflector::humanize($field) %>') ?></th>
<% endforeach; %>
                <th class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($<%= $singularVar %>-><%= $details['property'] %> as $<%= $otherSingularVar %>): ?>
            <tr>
            <%- foreach ($details['fields'] as $field): %>
                <%
                $primaryKeys = $schema->primaryKey();
                if (in_array($field, $primaryKeys)) {
                    continue;
                }

                if (in_array($field, $skipFields)) {
                    continue;
                }
                %>
                <td><?= h($<%= $otherSingularVar %>-><%= $field %>) ?></td>
            <%- endforeach; %>
            <%- $otherPk = "\${$otherSingularVar}->{$details['primaryKey'][0]}"; %>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => '<%= $details['controller'] %>', 'action' => 'view', <%= $otherPk %>]) %>
                    <?= $this->Html->link(__('Edit'), ['controller' => '<%= $details['controller'] %>', 'action' => 'edit', <%= $otherPk %>]) %>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => '<%= $details['controller'] %>', 'action' => 'delete', <%= $otherPk %>], ['confirm' => __('Are you sure you want to delete # {0}?', <%= $otherPk %>)]) %>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    </div>
<% endforeach; %>
</div>
