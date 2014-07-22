<h2><?php echo __('Local Encoding etc.');?></h2>


<h3><?php echo __('String / MB-String');?></h3>

<?php
	echo pre($stringInfos);
?>


<h3><?php echo __('Locales');?></h3>
<?php
$infos = array(
			'LC_TIME' => setlocale(LC_TIME, 0),
			'LC_NUMERIC' => setlocale(LC_NUMERIC, 0),
			'LC_MONETARY' => setlocale(LC_MONETARY, 0),
			'LC_CTYPE' => setlocale(LC_CTYPE, 0),
			'LC_COLLATE' => setlocale(LC_COLLATE, 0),
			'LC_MESSAGES' => @setlocale(LC_MESSAGES, 0).' (only on some systems available)',	// seems to run only on some systems
		);
		$res = '';
		foreach ($infos as $name => $content) {
			$res .= '<li><b>'.$name.':</b> '.$content.'</li>';
		}
		echo '<ul>'.$res.'</ul>';
?>