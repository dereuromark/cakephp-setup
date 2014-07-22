<h2><?php echo __('Cache');?></h2>

<h3>OpCodeCaches in use</h3>

<?php
	App::uses('OpCodeCacheLib', 'Setup.Lib');
	$is = OpCodeCacheLib::detect();
	echo pre($is);
?>




<?php
$settings = Cache::settings();
?>

<h3>Cache Testing</h3>
<?php
	$content = Cache::read('xyz');
	$useCache = false;
	if (empty($content)) {
		$content = '123456789 0';
		Cache::write('xyz', $content);
	} else {
		$useCache = true;
	}
?>

Cache benutzt: <?php echo $this->Format->yesNo($useCache);?>
<br />
Content:
<?php echo returns($content); ?>

<h3><?php echo __('Current Settings');?></h3>

<?php
	echo pre($settings);
?>

<h3>FileCaches available</h3>

<h4>File</h4>
JA

<h4>Eaccelator Shared</h4>
<?php
	if (function_exists('eaccelerator_put')) {
		echo 'JA';
	} else {
		echo 'NEIN';
	}
?>

<h4>Memcache</h4>
<?php
if (class_exists('Memcache')) {
	$memcache = new Memcache;
	$success = @$memcache->connect('localhost', 11211);
	echo 'JA - korrekt konfiguriert: ' . returns($success) . BR;
	echo 'Version: ';
	if (isset($success) && $success) {
		$version = $memcache->getVersion();
		echo $version;
	} else {
		echo '---';
	}
} else {
	echo 'NEIN (class \'Memcache\' does not exist!)';
}
?>

<h4>Memcached</h4>
<?php
if (class_exists('Memcached')) {
	$memcache = new Memcached;
	echo 'JA - installiert' . BR;
	echo 'Version: ';
	$version = $memcache->getVersion();
	echo print_r($version, true);

} else {
	echo 'NEIN (class \'Memcached\' does not exist!)';
}
?>