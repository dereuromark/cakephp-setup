<?php

$iterations = 12;
if (isset($_SERVER['PHP_SELF']))
	$phpSelf = $_SERVER['PHP_SELF'];
$phpSelf = env('REDIRECT_URL');

$starttime = explode(' ', microtime());
$string1 = 'abcdefghij';

for ($i = 1; $i <= 20000; $i++) {
	$x = $i * 5;
	$x = $x + $x;
	$x = $x / 10;
	$string3 = $string1 . strrev($string1);
	$string2 = substr($string1, 9, 1) . substr($string1, 0, 9);
	$string1 = $string2;
}

$endtime = explode(' ', microtime());
$totalTime = $endtime[0] + $endtime[1] - ($starttime[1] + $starttime[0]);
$totalTime = round($totalTime * 1000);

###################################################

$test = null;
if (isset($_GET['test']))
	$test = $_GET['test'];
$test = (int)$test;
if (empty($test))
	$test = 0;

if (isset($_GET['ttimes'])) {
	$ttimes = $_GET['ttimes'];
	if ($test > 0 && empty($ttimes)) {
		echo 'error';
		die;
	}
	$itimes = explode('_', $ttimes);
	if (count($itimes) < $test) {
		echo 'error 2';
		die;
	}
}

$itimes[$test] = number_format($totalTime, 0);
$testResults = '';
$ttimes2 = '';
$TimesSum = 0;

for ($i = 0; $i <= $test; $i++) {
	$itimes[$i] = (int)$itimes[$i];
	$TimesSum += $itimes[$i];
	$j = $i + 1;
	$testResults .= 'Test #' . $j . ' completed in ' . $itimes[$i] . ' ms.<br>';
	$ttimes2 .= $itimes[$i];
	if ($i < $test)
		$ttimes2 .= '_';
}

$test2 = $test + 1;
$tquery = 'test=' . $test2 . '&ttimes=' . $ttimes2;
$tquery2 = $tquery . '&stop=1;';
$AverageAll = round($TimesSum / $test2);
$iterations2 = $iterations - 1;
sort($itimes);
$lowest = $itimes[0];
$highest = $itimes[$test];
if (isset($_GET['stop']))
	$stop = $_GET['stop'];
if (isset($stop))
	$test = $iterations;

?>
<html><head>
<?php

if ($test < $iterations2)
	echo '<META HTTP-EQUIV="REFRESH" CONTENT="5; URL=' . $phpSelf . '?' . $tquery . '">';

?>
<title>Free PHP Benchmark Performance Script from Free-Webhosts.com</title>
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW"/>
<meta content="text/html; charset=windows-1252" http-equiv="content-type">
<meta http-equiv="Content-Style-Type" content="text/css">
</head>

<body>
<h2>Free PHP Benchmark Performance Script</h2>
<p><b><font face="Arial" color="#999999" size="4">
<?php

echo $testResults;
echo "<br>Lowest time: $lowest ms , Highest time : $highest ms<br>\n";
echo "Average of all $j times: <font size=\"+2\">$AverageAll ms</font><br>\n";
if ($test2 > 2) {
	$j -= 2;
	$AverageMid = round(($TimesSum - $lowest - $highest) / $j);
	echo "Average of middle $j times: <font size=\"+2\">$AverageMid ms</font><br>\n";
}
echo '<br><a href="' . $phpSelf . '">Begin again</a>';
if ($test < $iterations2)
	echo ' | <a href="' . $phpSelf . '?' . $tquery2 . '">Stop</a> | <font color=red>Doing ' . $iterations .
		' iterations. Refreshing in 5 seconds...</font><br>';

?>
</font></b></p>
</body></html>