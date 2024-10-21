<?php
/**
 * @var \App\View\AppView $this
 * @var array $freeSpace
 * @var array $space
 */
?>
<div class="col-md-12">

<h2><?php echo __('Disk Space'); ?></h2>

<?php
	$pos = count($space['app']) - 1;
	if (isset($space['app'][$pos])) {
		$string = $space['app'][$pos];
		$appSize = $string['size'];
	} else {
		$appSize = 0;
	}

?>

<h3>Free Space</h3>
Total Space: <?php echo $this->Number->toReadableSize($freeSpace['total']);?><br /><br />

<b><?php echo $this->Number->toReadableSize($freeSpace['available']);?> frei</b> (<?php echo h($freeSpace['percent_available']); ?>%),
<br />
<?php echo $this->Number->toReadableSize($freeSpace['used']);?> belegt (<?php echo h($freeSpace['percent_used']); ?>%)


<h3>Currently used space of project:</h3>
<?php echo $this->Number->toReadableSize($appSize);?>

</div>
