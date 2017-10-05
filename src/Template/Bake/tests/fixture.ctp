<?php
namespace <%= $namespace %>\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * <%= $name %>Fixture
 *
 */
class <%= $name %>Fixture extends TestFixture
{
<% if ($table): %>

    /**
     * Table name
     *
     * @var string
     */
    public $table = '<%= $table %>';
<% endif; %>
<% if ($import): %>

    /**
     * Import
     *
     * @var array
     */
    public $import = <%= $import %>;
<% endif; %>
<% if ($schema): %>

    /**
     * Fields
     *
     * @var array
     */
    public $fields = <%= $schema %>;
<% endif; %>
<% if ($records): %>

    /**
     * Records
     *
     * @var array
     */
    public $records = <%= $records %>;
<% endif; %>
}
