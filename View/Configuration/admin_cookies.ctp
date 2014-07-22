<h2><?php echo __('Cookies');?></h2>

<?php
foreach ($cookies as $name => $cookie) {
	echo '<h3>' . h($name) . '</h3>';
	echo pre($cookie);
	echo $this->Datetime->niceDate($cookie['time'], FORMAT_NICE_YMDHM);
}
?>
