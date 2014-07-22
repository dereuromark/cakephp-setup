<div class="page view">
<h2><?php echo __('old_browser_alert_test');?></h2>

<h3>Jquery</h3>
<div class="browserDetection"></div>
<script type="text/javascript">
$.each(jQuery.browser, function(i, val) {
	$("<div>" + i + " : <span>" + val + "</span>")
			.appendTo($('.browserDetection'));
});
</script>

<h3>JS</h3>
<div id="browserD">---</div>
<script type="text/javascript">
if (typeof document.body.style.maxHeight === 'undefined') {
		alert('ie6');
		document.getElementById('browserD').innerHTML = 'ie6';
} else {
		alert('not ie6');
		document.getElementById('browserD').innerHTML = 'not ie6';
}

</script>


<h3>IE-JS</h3>
<script type="text/javascript">
/*@cc_on @*/
/*@if (@_jscript_version <= 6.0)
	alert('Alter Browser!');
@else @*/
	//do nothing
/*@end @*/
</script>



<h3>Via PHP</h3>
<u>User-Agent</u>: <?php echo h($_SERVER['HTTP_USER_AGENT']);?>
<ul>
<?php
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
	echo "Microsofts Internet Explorer";
} else {
	echo 'kein IE';
}
?>
</ul>

</div>