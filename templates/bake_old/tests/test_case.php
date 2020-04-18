<%
use Cake\Utility\Inflector;

$isController = strtolower($type) === 'controller';

if ($isController) {
    $uses[] = 'Cake\TestSuite\IntegrationTestCase';
} else {
    $uses[] = 'Cake\TestSuite\TestCase';
}
sort($uses);

if (empty($fixtures)) {
    if ($subNamespace === 'Model\\Table') {
        $fixtures = [
            'app.' . $subject
        ];
    }
}

%>
<?php

namespace <%= $baseNamespace; %>\Test\TestCase\<%= $subNamespace %>;

<% foreach ($uses as $dependency): %>
use <%= $dependency; %>;
<% endforeach; %>

<% if ($isController): %>
class <%= $className %>Test extends IntegrationTestCase
{
<% else: %>
class <%= $className %>Test extends TestCase
{
<% endif; %>
<% if (!empty($properties)): %>
<% foreach ($properties as $propertyInfo): %>

    /**
     * <%= $propertyInfo['description'] %>
     *
     * @var <%= $propertyInfo['type'] %>
     */
    public $<%= $propertyInfo['name'] %><% if (isset($propertyInfo['value'])): %> = <%= $propertyInfo['value'] %><% endif; %>;
<% endforeach; %>
<% endif; %>
<% if (!empty($fixtures)): %>

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [<%= $this->Bake->stringifyList(array_values($fixtures), ['trailingComma' => true]) %>];
<% endif; %>
<% if (!empty($construction)): %>

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    <%- if ($preConstruct): %>
        <%= $preConstruct %>
    <%- endif; %>
        $this-><%= $subject . ' = ' . $construction %>
    <%- if ($postConstruct): %>
        <%= $postConstruct %>
    <%- endif; %>
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this-><%= $subject %>);

        parent::tearDown();
    }
<% endif; %>
<% if ($subNamespace === 'Model\\Table'): %>
    /**
     * @return void
     */
    public function testInstance() {
        $this->assertInstanceOf(<%= $className %>::class, $this-><%= $subject %>);
    }

    /**
     * @return void
     */
    public function testFind() {
        $result = $this-><%= $subject %>->find()->first();
        $this->assertTrue(!empty($result));
        $this->assertInstanceOf(\<%= $baseNamespace; %>\Model\Entity\<%= Inflector::singularize($subject); %>::class, $result);
    }
<% endif; %>
<% foreach ($methods as $method): %>

    /**
     * Test <%= $method %> method
     *
     * @return void
     */
    public function test<%= Inflector::camelize($method) %>()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
<% endforeach; %>
<% if (empty($methods)): %>

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
<% endif; %>
}
