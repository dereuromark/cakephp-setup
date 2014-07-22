<h2><?php echo __('Current/Active Configuration');?></h2>

<?php
/*
<h3>NEW WAY: - Current Settings - stored as CONSTANTS</h3>
Use these:<br />
<pre>
<?php
$config=Configure::read('Config');
foreach ($config as $var => $value) {

	//if (defined('CONFIG_'.$var)) { echo CONFIG_$var;}
	echo 'GLOBAL_'.strtoupper($var).' '.$value.'<br>';
}

?>
</pre>
*/ ?>

<h3>Current Setting - stoed in Configure::read(null)</h3>

<ul><?php
echo pre(Configure::read(null));
?>
</ul>


This content can be read out anywhere - view/controller/model etc