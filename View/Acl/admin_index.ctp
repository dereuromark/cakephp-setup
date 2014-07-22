<script type="text/javascript">
/*
$(document).ready(function() {
	$('.tree li').click(function() {
		$(this).children('ul').slideToggle("slow");
	});
});
*/
</script>

<div class="span-21 last">
<?php
$this->loadHelper('Tools.Tree');
?>

<div class="tree">
<?php
echo $this->Tree->generate($acos, array('model' => 'Aco', 'alias' => 'alias'));
?>
</div>
</div>