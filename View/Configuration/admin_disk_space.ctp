<h2>Disk Space</h2>
<?php
	$pos = count($space['app']) - 1;
	if (isset($space['app'][$pos])) {
		$string = $space['app'][$pos];
		$appSize = $string['size'];
	} else {
		$appSize = 0;
	}

	$pos = count($space['cake']) - 1;
	if (isset($space['cake'][$pos])) {
		$string = $space['cake'][$pos];
		$cakeSize = $string['size'];
	} else {
		$cakeSize = 0;
	}

	$pos = count($space['vendors']) - 1;
	if (isset($space['vendors'][$pos])) {
		$string = $space['vendors'][$pos];
		$vendorsSize = $string['size'];
	} else {
		$vendorsSize = 0;
	}

	if (!isset($this->Numeric)) {
		App::import('Helper', 'Tools.Numeric');
		$this->Numeric = new NumericHelper(new View(null));
	}
?>

<h3>Free Space</h3>
Total Space: <?php echo $this->Numeric->toReadableSize($freeSpace['total']);?><br /><br />

<b><?php echo $this->Numeric->toReadableSize($freeSpace['available']);?> frei</b> (<?php echo h($freeSpace['percent_available']); ?>%),
<br />
<?php echo $this->Numeric->toReadableSize($freeSpace['used']);?> belegt (<?php echo h($freeSpace['percent_used']); ?>%)


<h3>Currently used space of project:</h3>
Cake: <?php echo $this->Numeric->toReadableSize($cakeSize);?><br />
Vendors: <?php echo $this->Numeric->toReadableSize($vendorsSize);?><br />
<br />
App: <b><?php echo $this->Numeric->toReadableSize($appSize);?></b>

<ul>
<?php
	//TODO: make tree!!!

	//echo pre ($space);
	foreach ($space['app'] as $s) {
		echo '<li>';
		//echo str_replace($ro)
		$path = str_replace($appPath, '', $s['path']);
		if (empty($path)) {
			$path = '/';
		}
		echo $path . ' - ' . $this->Numeric->toReadableSize($s['size']);
		echo '</li>';
	}


?>
</ul>



<h3>Example Tree </h3>

<?php echo $this->Html->script('/setup/js/jquery/treeview/jquery.treeview.js', true);?>
<?php echo $this->Html->css('/setup/js/jquery/treeview/jquery.treeview.css', array('inline' => true));?>

<script type="text/javascript">
$(document).ready(function() {
	$("#example").treeview();
});
</script>


<ul id="example" class="filetree">
		<li><span class="folder">Folder 1</span>
			<ul>
				<li><span class="file">Item 1.1</span></li>
			</ul>
		</li>
		<li><span class="folder">Folder 2</span>
			<ul>
				<li><span class="folder">Subfolder 2.1</span>
					<ul>
						<li><span class="file">File 2.1.1</span></li>
						<li><span class="file">File 2.1.2</span></li>
					</ul>
				</li>
				<li><span class="file">File 2.2</span></li>
			</ul>
		</li>
		<li class="closed"><span class="folder">Folder 3 (closed at start)</span>
			<ul>
				<li><span class="file">File 3.1</span></li>
			</ul>
		</li>
		<li><span class="file">File 4</span></li>
</ul>

<br />