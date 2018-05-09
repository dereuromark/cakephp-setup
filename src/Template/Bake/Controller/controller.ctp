<%
use Cake\Utility\Inflector;
%>
<?php
namespace <%= $namespace %>\Controller<%= $prefix %>;

use <%= $namespace %>\Controller\AppController;

/**
<% if ($defaultModel): %>
 * @property \<%= $defaultModel %> $<%= $name %>
<% endif; %>
<%
foreach ($components as $component):
    $classInfo = $this->Bake->classInfo($component, 'Controller/Component', 'Component');
%>
 * @property <%= $classInfo['fqn'] %> $<%= $classInfo['name'] %>
<% endforeach; %>
<% if (in_array('index', $actions)): %>
 *
 * @method \<%= $namespace %>\Model\Entity\<%= $entityClassName %>[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
<% endif; %>
 */
class <%= $name %>Controller extends AppController
{
<%
echo $this->Bake->arrayProperty('helpers', $helpers, ['indent' => false]);
echo $this->Bake->arrayProperty('components', $components, ['indent' => false]);
foreach ($actions as $action) {
    echo $this->element('Controller/' . $action);
}
%>
}
