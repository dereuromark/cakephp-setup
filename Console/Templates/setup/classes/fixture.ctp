<?php echo '<?php' . "\n"; ?>
/**
 * <?php echo $model; ?>Fixture
 *
 */
class <?php echo $model; ?>Fixture extends CakeTestFixture {

<?php if ($table): ?>
	/**
	 * Table name
	 *
	 * @var string
	 */
	public $table = '<?php echo $table; ?>';
<?php endif; ?>
<?php if ($import): ?>
	/**
	 * Import
	 *
	 * @var array
	 */
	public $import = <?php echo $import; ?>;
<?php endif; ?>
<?php if ($schema): ?>
	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = <?php echo $schema; ?>;
<?php endif;?>

<?php if ($records): ?>
	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = <?php echo $records; ?>;
<?php endif; ?>
}
