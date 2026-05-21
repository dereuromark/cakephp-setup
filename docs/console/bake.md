# Bake Templates

The plugin ships enhanced bake templates. The defaults work well together with the
Tools plugin, Bootstrap 3+, and several other useful conventions. You can also
just steal ideas, of course.

Use these templates with the `--theme`/`-t` option:

```bash
bin/cake bake -t Setup ...
```

See the [CakePHP bake docs](https://book.cakephp.org/bake/2/en/development.html)
for how to customize further.

## Model level

By default these templates use the Tools plugin's Entity and Table classes in
their `use` statements. You can avoid this by providing an
`App\Model\Entity\Entity` and/or `App\Model\Table\Table` base class in your
application. Bake then detects those and uses them instead. In those files you can
extend the Tools, Shim, or CakePHP core classes.

For example, for your `App\Model\Entity\Entity`:

```php
namespace App\Model\Entity;

use Cake\ORM\Entity as CakeEntity;

class Entity extends CakeEntity {
}
```

## Controller level

By default the controller templates use `compact()` to pass variables down. This
makes those variables easier to debug in the controller scope.

## View level

A few additional Table class configs let you customize field output.

By default, index pages do not print the primary key(s). This is usually
irrelevant information and can also be retrieved from the action links if
necessary, which saves some column space.

### Skipping fields

Use `public $skipFields = [...]` in your Table class to skip certain fields from
being output. By default the following fields are excluded:

```php
['password', 'slug', 'lft', 'rght', 'created_by', 'modified_by', 'approved_by', 'deleted_by']
```

Use one of the following to skip fields for a specific action type:

- `$scaffoldSkipFieldsIndex`
- `$scaffoldSkipFieldsView`
- `$scaffoldSkipFieldsForm`

### Pagination order defaults

`$paginationOrderReversedFields` and `$paginationOrderReversedFieldTypes` set the
defaults for column sorting in paginations. The defaults are:

```php
$paginationOrderReversedFields = ['published', 'rating', 'priority'];
$paginationOrderReversedFieldTypes = ['datetime', 'date', 'time', 'bool'];
```

### Better auto-display of fields

Text fields are not shown on the index, to avoid overcrowding.

On top of the existing detection, the following fields are auto-detected and
formatted accordingly:

- `tinyint(2)` of type enum, as per the Tools plugin enum solution (the plural
  static method of the field): `$entity::$method()`.
- `['date', 'time', 'datetime', 'timestamp']`: `$this->Time->nice()`.
- `['boolean']`: `$this->Format->yesNo()` using the `Tools.Format` helper.

## See also

- [Console Commands](/console/) — the broader operational toolbox.
