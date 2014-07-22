<div class="page index">
<?php
/**
 * TODO:
 * - toggle sample text ON/OFF (either through stylesheets or $_GET[] parameters$)
 * - toggle the sample text color (lighter - darker) for better visibilty of the tags
 * - clearer structure on site
 */

$mysampletext = '<span class="sample_text">.. here is some sample text before and after the tag..</span>';

$xhtmlBlock = array();

$xhtmlBlock[] = array(	'h1',
				'Header 1',
				'Some heading'
				);
$xhtmlBlock[] = array(	'h2',
				'Header 2',
				'Some heading'
				);
$xhtmlBlock[] = array(	'h3',
				'Header 3',
				'Some heading'
				);
$xhtmlBlock[] = array(	'h4',
				'Header 4',
				'Some heading'
				);
$xhtmlBlock[] = array(	'h5',
				'Header 5',
				'Some heading'
				);
$xhtmlBlock[] = array(	'h6',
				'Header 6',
				'Some heading'
				);
$xhtmlBlock[] = array(	'p',
				'Paragraph',
				'This is a new paragraph'
				);
$xhtmlBlock[] = array(	'address',
				'Identify contact information for a document',
				'Some address - PO Box xyz'
				);
$xhtmlInline[] = array(	'code',
				'Computer Code',
				'Sample Code'
				);
$xhtmlBlock[] = array(	'pre',
				'Preformatted Text',
				'Sample Code'
				);
$xhtmlBlock[] = array(	'fieldset',
				'Form control group',
				'Form Content'
				);
$xhtmlBlock[] = array(	'legend',
				'Fieldset caption',
				'I am a Legend (works only inside &lt;fieldset&gt;)'
				);


$xhtmlInline = array();

$xhtmlInline[] = array(	'a',
				'Link/Anchor',
				'Sample Link/Anchor (without href)'
				);
$xhtmlInline[] = array(	'b',
				'Bold',
				'Important text'
				);
$xhtmlInline[] = array(	'strong',
				'Strong',
				'Important text'
				);
$xhtmlInline[] = array(	'del',
				'Strike trough',
				'Not valid anymore'
				);
$xhtmlInline[] = array(	'sup',
				'Superscript',
				'Some text'
				);
$xhtmlInline[] = array(	'sub',
				'Subscript',
				'Some text'
				);
$xhtmlInline[] = array(	'em',
				'Indicates emphasis',
				'Some emphasized text'
				);
$xhtmlInline[] = array(	'q',
				'Short quotation',
				'Some quoted text'
				);
$xhtmlInline[] = array(	'acronym',
				'Acronym - needs a title-attr!',
				'e.g. HTML - stands for Hypertext Markup Language'
				);
$xhtmlInline[] = array(	'abbr',
				'Abbreviation - needs a title-attr!',
				'Like P.S.'
				);
$xhtmlInline[] = array(	'var',
				'Variable Define',
				'Defines a variable'
				);
$xhtmlInline[] = array(	'cite',
				'Citation',
				'Some Citation here'
				);
$xhtmlInline[] = array(	'kbd',
				'Sample Computer Code',
				'SHIFT STRG ALT 1'
				);
$xhtmlInline[] = array(	'dfn',
				'Definition Term',
				'application/config/database.php'
				);
$xhtmlInline[] = array(	'samp',
				'Keyboard Text',
				'Sample Code'
				);
$xhtmlInline[] = array(
	'label',
	'Form element label',
	'I am a Label'
);

?>

<style type="text/css">
table.reference {
	width: 99%;
	border-collapse: separate;
	border-spacing: 1px;
}
table.reference td {
	padding:4px;
	border: 1px solid #cccccc;
}
table.reference td.def {
	width:210px;
}
span.sample_text {
	/* color: #999999; */
}


</style>

<h2>XHTML Reference (quick checkup)</h2>
I use this site to check if all tags used somewhere on the website are defined and layouted as wanted.
<br />Especially after altering the layout - or changing to a complete new one, this page helps to find forgotten css-rules or some
different appearance problems in different browsers. It just combines all tags on one single page. Some "nonsense" text is added before/after for better view of the current margin/padding-settings and if the line breaks or not (there is just one single whitespace in between).
<br /><br />

<h3>Block elements</h3>
<table class="reference">
<?php
foreach ($xhtmlBlock as $x) {
	echo '<tr>';
	echo '<td class="def"><b>&lt;' . $x[0] . '&gt;..&lt;/' . $x[0] . '&gt;</b><br/>(' . $x[1] . ')</td>';

	$sampletext = $mysampletext;
	echo '<td>' . $sampletext . ' <' . $x[0] . '>' . $x[2] . '</' . $x[0] . '> ' . $sampletext . '</td>';

	echo '<tr>';
}

# Special ones:
	echo '<tr>';
	echo '<td class="def"><b>&lt;hr /&gt;</b><br/>(Content Seperator)</td>';
	$sampletext = $mysampletext;
	echo '<td>' . $sampletext . ' <hr /> ' . $sampletext . '</td>';
	echo '<tr>';
?>
</table>

<br /><br />

<h3>Inline elements</h3>
<table class="reference">
<?php
foreach ($xhtmlInline as $x) {
	echo '<tr>';
	echo '<td class="def"><b>&lt;' . $x[0] . '&gt;..&lt;/' . $x[0] . '&gt;</b><br/>(' . $x[1] . ')</td>';

	$sampletext = $mysampletext;
	echo '<td>' . $sampletext . ' <' . $x[0] . '>' . $x[2] . '</' . $x[0] . '> ' . $sampletext . '</td>';

	echo '<tr>';
}

# Special ones:
	echo '<tr>';
	echo '<td class="def"><b>&lt;a&gt;..&lt;/a&gt;</b><br/>(A href Link)</td>';
	$sampletext = $mysampletext;
	echo '<td>' . $sampletext . ' <a href="javascript:void(0)"/>This is a real link</a> ' . $sampletext . '</td>';
	echo '<tr>';
?>
</table>

<br /><br />

<h3>Form elements</h3>
<?php
$xhtmlInline = array();

$xhtmlInline[] = array(
	'input',
	'Form input button',
	false,
	'type="submit"'
);

$xhtmlInline[] = array(
	'button',
	'Form element button',
	$this->Format->cIcon(ICON_EDIT) . ' I am a button',
	'class="button"',
);

$xhtmlInline[] = array(
	'a',
	'Form link button',
	$this->Format->cIcon(ICON_EDIT) . ' I am a button link',
	'href="javascript:void(0)" class="button"'
);
?>
<table class="reference">
<?php
foreach ($xhtmlInline as $x) {
	echo '<tr>';
	echo '<td class="def"><b>&lt;' . $x[0] . '&gt;..&lt;/' . $x[0] . '&gt;</b><br/>(' . $x[1] . ')</td>';

	$sampletext = $mysampletext;
	echo '<td>' . $sampletext . ' <div class="buttons"><' . $x[0] . '' . (!empty($x[3]) ? ' ' . $x[3] : '') . '>' . $x[2] . '</' . $x[0] . '></div> ' . $sampletext . '</td>';

	echo '<tr>';
}
?>
</table>

<br /><br />


<h3>List elements</h3>
<table class="reference">
<?php
	echo '<tr>';
	echo '<td class="def"><b>&lt;ul&gt;..&lt;/ul&gt;</b><br/>(ul/li Unordered List)</td>';
	$sampletext = $mysampletext;
	echo '<td>' . $sampletext . ' <ul><li>list item 1</li><li>some other list item</li></ul> ' . $sampletext . '</td>';
	echo '<tr>';

	echo '<tr>';
	echo '<td class="def"><b>&lt;ul&gt;..&lt;/ul&gt;</b><br/>(ol/li Ordered List)</td>';
	$sampletext = $mysampletext;
	echo '<td>' . $sampletext . ' <ol><li>list item 1</li><li>some other list item</li></ol> ' . $sampletext . '</td>';
	echo '<tr>';
?>
</table>


<br /><br />

<h3>Some more things</h3>
<ul>
<li>Choose between &lt;b&gt; and &lt;strong&gt; and stick to it (or use &lt;span&gt; and CSS)</li>
<li>Its now &lt;br /&gt; instead of just &lt;br&gt;</li>
<li>Same for all single tags: &lt;input /&gt;, &lt;link /&gt;,  &lt;hr /&gt;</li>
<li>Attributes are never for themselves:<br />checked="checked", selected="selected", disabled="disabled" etc</li>
</ul>

<br />

<h3>Not to use anymore</h3>
As they can be decleared through CSS in combination with &lt;span&gt;/&lt;span&gt; etc.
<ul>
<li>&lt;s&gt; (strike)</li>
<li>&lt;u&gt; (underline)</li>
<li>&lt;i&gt; (italic)</li>
<li>&lt;center&gt;</li>
<li>&lt;font&gt;</li>
<li>&lt;basefont /&gt;</li>
<li>&lt;embed&gt;</li>
<li>&lt;blockquote&gt;</li>
<li>&lt;tt&gt;</li>
<li>&lt;big&gt; / &lt;small&gt;</li>
</ul>

</div>