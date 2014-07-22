<script type="text/javascript">
function toggleMe(id) {
	var e=document.getElementById(id);
	if (!e)return true;
	if (e.style.display=="none") {
		e.style.display="block"
	} else {
		e.style.display="none"
	}
	return true;
}
function untoggleMe(a) {
	var e=document.getElementById(a);
	if (!e)return true;
	if (e.style.display=="block") {
		e.style.display="none"
	} else {
		e.style.display="block"
	}
	return true;
}

$(document).ready(function() {

	$('#toggleAllLink').click(function() {
		$('.toggleAll').toggle();
	});
});
</script>

<h2><?php echo __('Constants and their Content');?></h2>
<?php
$constsNotDefined = array();
$output = '';
?>

<h3><?php echo __('PHP');?></h3>
<?php
	if (defined('PHP_VERSION')) {
		echo 'PHP_VERSION = ' . PHP_VERSION . '<br>';
	} else {$constsNotDefined[] = 'PHP_VERSION';}
	if (defined('PHP_EOL')) {
		echo 'PHP_EOL = ' . PHP_EOL . ' <small>(automatically correct new line / system)</small><br>';
	} else {$constsNotDefined[] = 'PHP_EOL';}
?>


<h3><?php echo __('Core Defines');?></h3>

<?php
	if (defined('DS')) {
	$output .= 'DS = ' . DS . '<br>';
		} else {$constsNotDefined[] = 'DS';}

	if (defined('LOG_ERROR')) {
	$output .= 'LOG_ERROR = ' . LOG_ERROR . '<br>';
		} else {$constsNotDefined[] = 'LOG_ERROR';}

	$output .= '<br/><h3>Misc.</h3>';
	$output .= 'SECOND, MINUTE, HOUR, DAY, WEEK, MONTH, YEAR';

	$output .= '<br/><h3>Webroot Configurable Paths</h3>';

	if (defined('CORE_PATH')) {
		$output .= 'CORE_PATH = ' . CORE_PATH . ' <small>(usually not needed)</small><br>';
	} else {$constsNotDefined[] = 'CORE_PATH';}

	$output .= 'WWW_ROOT = ' . WWW_ROOT . '<br>';
	$output .= 'ROOT = ' . ROOT . '<br>';
	$output .= 'WEBROOT_DIR = ' . WEBROOT_DIR . '<br>';


	$output .= '<br/><h3>Paths</h3>';
	$output .= 'APP = ' . APP . '<br>';
	$output .= 'APP_DIR = ' . APP_DIR . '<br>';

	if (defined('APP_PATH')) {
		$output .= 'APP_PATH = ' . APP_PATH . ' <small>(usually not needed)</small><br>';
	} else {$constsNotDefined[] = 'APP_PATH';}


	//$output .= 'BEHAVIORS = '.BEHAVIORS.'<br>';
	$output .= 'CACHE = ' . CACHE . '<br>';
	$output .= 'CAKE = ' . CAKE . '<br>';
	//$output .= 'COMPONENTS = '.COMPONENTS.'<br>';
	//$output .= 'CONFIGS = '.CONFIGS.'<br>';
	//$output .= 'CONSOLE_LIBS = '.CONSOLE_LIBS.'<br>';
	//$output .= 'CONTROLLER_TESTS = '.CONTROLLER_TESTS.'<br>';
	//$output .= 'CONTROLLERS = '.CONTROLLERS.'<br>';
	$output .= 'CSS = ' . CSS . '<br>';
	//$output .= 'ELEMENTS = '.ELEMENTS.'<br>';
	$output .= 'FULL_BASE_URL = ' . FULL_BASE_URL . ' (!)<br>';
	//$output .= 'HELPER_TESTS = '.HELPER_TESTS.'<br>';
	//$output .= 'HELPERS = '.HELPERS.'<br>';


	$output .= 'IMAGES = ' . IMAGES . '<br>';
	$output .= 'JS = ' . JS . '<br>';
	//$output .= 'LAYOUTS = '.LAYOUTS.'<br>';
	//$output .= 'LIB_TESTS = '.LIB_TESTS.'<br>';
	//$output .= 'LIBS = '.LIBS.'<br>';
	$output .= 'APPLIBS = ' . APPLIBS . '<br>';
	//$output .= 'MODEL_TESTS = '.MODEL_TESTS.'<br>';
	//$output .= 'MODELS = '.MODELS.'<br>';
	//$output .= 'PEAR = '.PEAR.' <small>// purporse is to make it easy porting Pear libs into Cake</small><br>';

	if (defined('SCRIPTS')) {
		$output .= 'SCRIPTS = ' . SCRIPTS . '<br>';
	} else {$constsNotDefined[] = 'SCRIPTS';}

	$output .= 'TESTS = ' . TESTS . '<br>';
	$output .= 'TMP = ' . TMP . '<br>';
	$output .= 'LOGS = ' . LOGS . '<br>';
	$output .= 'VENDORS = ' . VENDORS . '<br>';
	//$output .= 'VIEWS = '.VIEWS.'<br>';

	$output .= '<br><b>Webpaths:</b><br>';
	$output .= 'IMAGES_URL = ' . IMAGES_URL . '<br>';
	$output .= 'CSS_URL = ' . CSS_URL . '<br>';
	$output .= 'JS_URL = ' . JS_URL . '<br>';


	$output .= '<br/><h3>Own Paths</h3>';

	if (defined('HTTP_HOST')) {
		$output .= 'HTTP_HOST = ' . HTTP_HOST . '<br>';
	} else {$constsNotDefined[] = 'HTTP_HOST (own)';}

	if (defined('HTTP_BASE')) {
		$output .= 'HTTP_BASE = ' . HTTP_BASE . '<br>';
	} else {$constsNotDefined[] = 'HTTP_BASE (own)';}

	if (defined('HTTP_SELF')) {
		$output .= 'HTTP_SELF = ' . HTTP_SELF . '<br>';
	} else {$constsNotDefined[] = 'HTTP_SELF (own)';}

	if (defined('HTTP_URI')) {
		$output .= 'HTTP_URI = ' . HTTP_URI . '<br>';
	} else {$constsNotDefined[] = 'HTTP_URI (own)';}

	if (defined('HTTP_REF')) {
		$output .= 'HTTP_REF = ' . HTTP_REF . '<br>';
	} else {$constsNotDefined[] = 'HTTP_REF (own)';}


	if (defined('HTTP_REL')) {
		$output .= 'HTTP_REL = ' . HTTP_REL . '<br>';
	} else {$constsNotDefined[] = 'HTTP_REL (own)';}

	if (defined('PATH_REL')) {
		$output .= 'PATH_REL = ' . PATH_REL . '<br>';
	} else {$constsNotDefined[] = 'PATH_REL (own)';}

	if (defined('PATH_HOME')) {
		$output .= 'PATH_HOME = ' . PATH_HOME . '<br>';
	} else {$constsNotDefined[] = 'PATH_HOME (own)';}

	if (defined('ICONS')) {
		$output .= 'ICONS = ' . ICONS . '<br>';
	} else {$constsNotDefined[] = 'ICONS (own)';}

	if (defined('SMILEYS')) {
		$output .= 'SMILEYS = ' . SMILEYS . '<br>';
	} else {$constsNotDefined[] = 'SMILEYS (own)';}

	if (defined('FILES')) {
		$output .= 'FILES = ' . FILES . '<br>';
	} else {$constsNotDefined[] = 'FILES (own)';}

	$output .= '<br/><h3>Own Defines</h3>';
	//$output .= 'DEFAULT_LANGUAGE = '.DEFAULT_LANGUAGE.'<br>';
	$output .= 'WINDOWS = ' . (int)WINDOWS . '<br>';
	$output .= 'BR, LF, NL, TB<br>';

/** Output */
		echo $output;


if (count($constsNotDefined) > 0)
{
	echo '<br><h3>Not correctly defined constants:</h3><ul>';
	foreach ($constsNotDefined as $c) {
		echo '<li>' . $c . '</li>';
	}
	echo '</ul>';
}


?>


<br />
<h3>Full reference of ALL set constants</h3>
<a href="javascript:void(0)" id="toggleAllLink">Show All</a>
<ul>
<?php
$getDefinedConstants = get_defined_constants(true);

foreach ($getDefinedConstants as $definedConstants => $constants) {
	echo '<li>' . $this->Html->link($definedConstants, 'javascript:void(0)', array('onclick' => 'toggleMe(\'const-' . $definedConstants . '\')'));
	echo '<div style="' . ($definedConstants == 'user' ? 'display:block' : 'display:none') . '" class="toggleAll" id="const-' . $definedConstants . '">';
	echo '<br>';
	echo pre($constants);
	echo '</li>';
}
?>
</ul>