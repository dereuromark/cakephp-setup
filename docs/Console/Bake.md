# Setup plugin Bake templates

You can use these templates by using the `--theme`/`-t` option:
```
bin/cake bake -t Setup ...
```

See [CakePHP docs](http://book.cakephp.org/3.0/en/bake/development.html#extending-bake) on how to further customize.

## Model level
By default uses Tools plugin Entity and Table classes in use statements.
You can easily avoid this by having an `App\Model\Entity\Entity` and/or `App\Model\Table\Table` base class in your application.
It will then detect those and use them instead. In those files you can then extend the Tools, Shim or CakePHP core classes.

E.g. for your `App\Model\Entity\Entity`:
```php
namespace App\Model\Entity;

use Cake\ORM\Entity as CakeEntity;

class Entity extends CakeEntity {
}
```

## Controller level
By default it uses compact() to pass down variables. Makes debugging them easier in the controller scope.

## View level
A few additional Table class configs can be added to customize field output.

By default the index pages do not print out the primary key(s). This is usually irrelevant info and can also be retrieved from the action links if necessary.
Saves some column space.

### Skipping fields
Use `public $skipFields = [...]` in your Table class to skip certain fields from being outputted.
By default the following fields are already excluded:
```php
['password', 'slug', 'lft', 'rght', 'created_by', 'modified_by', 'approved_by', 'deleted_by']
```

Use one of the following to skip for a specific action type:
- `$scaffoldSkipFieldsIndex`
- `$scaffoldSkipFieldsView`
- `$scaffoldSkipFieldsForm`

### Pagination order defaults
`$paginationOrderReversedFields` and `$paginationOrderReversedFieldTypes` help to set the defaults for column sorting in paginations.
The defaults currently are:
```php
$paginationOrderReversedFields = ['published', 'rating', 'priority'];
$paginationOrderReversedFieldTypes = ['datetime', 'date', 'time', 'bool'];
```

### Better auto-display of fields
The grouping of all fields has been removed, there is only text (paragraphs at the end) and default (all other field types) now.

The following fields are auto-detected on top of the existing ones and formatted accordingly:

- Default string fields: h()
- ['integer', 'float', 'decimal', 'biginteger']: $this->Number->format()
- int(2) of type enum as per Tools plugin enum solution (plural static method of the field): $entity::$method()
- ['date', 'time', 'datetime', 'timestamp']: $this->Time->nice()
- ['boolean']: $this->Format->yesNo() using Tools.Format helper
- ['text']: $this->Text->autoParagraph() below all all other fields

## TODO
- Custom sorting of fields, if the DB cannot provide a good default order, auto-prio displayField as 1st, created/modified as last.
- port $schema[$field]['type'] === 'decimal' || $schema[$field]['type'] === 'float' && strpos($schema[$field]['length'], ',2') as money formatting
