<h2>Step 1</h2>

<h3>Database</h3>
<pre>/APP/Config/database.php</pre>
<?php
	$databaseConfiguration = InstallLib::databaseConfigurationExists();
?>
<div>
<?php //echo $this->Format->yesNo(); ?>
<?php if ($databaseConfiguration) { ?>
File exists
	<?php if (($databaseConfigurationStatus = InstallLib::databaseConfigurationStatus()) === true) { ?>
	 - default datasource OK
	<?php } else { ?>
	- default datasource NOT OK (<?php echo h($databaseConfigurationStatus)?>)
	<?php } ?>

<?php } else { ?>
File does not exist
<?php } ?>
</div>

<div>

<?php echo @$this->Form->create('Install'); ?>
<fieldset>
	<legend>Basics</legend>
<?php
	echo $this->Form->input('datasource');
	echo $this->Form->input('database');
	echo $this->Form->input('prefix');
	echo $this->Form->input('host');
	echo $this->Form->input('login');
	echo $this->Form->input('password');
	echo $this->Form->input('persistent', array('type' => 'checkbox', ));
	echo $this->Form->input('encoding');

echo $this->Form->input('enhanced_database_class', array('type' => 'checkbox', 'label' => __('Use enhanced database class (Setup plugin)')));

?>
</fieldset>
<fieldset>
	<legend>For enhanced class only</legend>
<?php
	echo $this->Form->input('name');
	echo $this->Form->input('environment');
?>
</fieldset>

<?php echo $this->Form->submit(__('Continue'));?>
<?php echo $this->Form->end(); ?>

</div>


			'dbuser',
			'dbpassword',
			'dbname',
			'sitename',
			'wwwroot',
			'dataroot',
			'displayname',
			'email',
			'username',
			'password',