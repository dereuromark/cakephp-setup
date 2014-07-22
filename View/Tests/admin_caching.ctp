<h2><?php echo __('Caching');?></h2>

<?php
if (!empty($content)) {

	echo substr($content, 0, 2);
	echo BR . BR;
?>
	<?php echo '<?php $content = "' . h($content) . '";?>'?>
	<!--nocache-->
	<?php echo $this->Datetime->niceDate(time(), FORMAT_NICE_YMDHMS)?>
	<?php echo substr($content, 3, 5)?>
	<!--/nocache-->
	<br /><br />
<?php
	echo $content;


}
?>