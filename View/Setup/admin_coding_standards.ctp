<?php
/**
 * TODO:
 * -
 */


?>

<style type="text/css">


</style>


<h2>Coding Standards (extend the CakePHP ones)</h2>
The CakePHP Coding Standards can be found <?php echo $this->Html->link('here', 'https://trac.cakephp.org/wiki/Developement/CodingStandards', array('target' => '_blank'))?>.
Except for the usage of the ternary operator (?:) - at least in some cases - I totally agree on the standards of the CakePHP team.<br />
This page here was mainly for myself - but i thought i could publish it as well.<br />
You dont have to to it the same way, but some tips and standards might be useful to stick to. And if you turn something in - like a modul or s.th. - it will help the scripts to stay transparent and extendable.
<br /><br />

<h4>Important Definitions</h4>
<code>
<b>CamelCase</b>: all the words have a capital - no whitespaces<br />
<b>camelBack</b>: first word has a minuscule, all following ones have a capitel<br /><br />

<b>function</b>: normal php function (for itself)<br />
<b>method</b>: function INSIDE a class - usually the case in CakePHP<br />
<b>object</b>: instantiated class
</code>

<br />
<h4>Table of Content</h4>
<?php
	$currentUrl = $this->Html->url();
?>
<ul class="table_of_content">
<li><a href="<?php echo $currentUrl;?>#php">PHP</a></li>
<li><a href="<?php echo $currentUrl;?>#db">MYSQL (or DB in general)</a></li>
<li><a href="<?php echo $currentUrl;?>#html">HTML</a></li>
<li><a href="<?php echo $currentUrl;?>#css">CSS</a></li>
<li><a href="<?php echo $currentUrl;?>#js">JS (Javascript)</a></li>
<li><a href="<?php echo $currentUrl;?>#doc">Documentation</a></li>
<li><a href="<?php echo $currentUrl;?>#principles">Cake Principles</a></li>
</ul>


<br />



<h3 id="php">PHP</h3>

<h4>Following CakePHP (quick summary):</h4>
The { open bracket is in the same line of the function name(as shown below in the "function edit()").
<br /><br />

Objects are in CamelCase (and should represent the class name):
<?php
$dataPrint = 'App::import(\'Core\', \'File\');
$FileUtil =& new FileUtil(...);';
echo $this->Geshi->highlight($dataPrint, 'php');
?>

Methods and variables usually are in camelBack:
<?php
$dataPrint = 'function xyzAbc($someVar) {
	...
	return $someOtherVar;
}';
echo $this->Geshi->highlight($dataPrint, 'php');
?>

There is an exception in cake though:<br />
All controller methods that are actually a url link (can be accessed trough .../your_site/controller_name/method_action/...)
can have "admin_" oder "rss_" etc in front of them - connected though an underscore (e.g. admin_postEdit).
<br />
And I usually attach "_ajax" to a method, that can be accessed through the browser but is intended for ajax requests only (for better visibility):<br />
function deletePost_ajax() {} - as there can still be a normal deletePost method that maybe a normal page.
<br /><br />

The example shows how to set the brackets as well. same like in css and js.<br />
All code lines should be indented properly - this improves readability.
<br /><br />
<h4>Security Issues</h4>
As shown in the following examples (and lots of blogs etc), should be implemented on all pages.<br />
that includes typecasting, escaping and validation of variables wherever needed.<br /><br />

'Controller' Example:
<?php
$dataPrint = 'function edit($id) {
	if (empty($id) || !($r = $this->Model->find(...)) {
		// error
	}
	// continue
}';
echo $this->Geshi->highlight($dataPrint, 'php');
?>
This is mainly for /view, /edit and /delete methods, where usually a primary id (aiid/uuid in cake) is used to identify the correct entry.


<br /><br />

'View' Example:
<?php
$dataPrint = 'echo h($comment); // to escape database content prior to displaying it';
echo $this->Geshi->highlight($dataPrint, 'php');
?>

which prints out (source code):
<?php
$dataPrint = 'Some text &lt;script&gt;alert(\'gotcha! attack!\')&lt;/script&gt; gotcha';
echo $this->Geshi->highlight($dataPrint, 'text');
?>

instead of
<?php
$dataPrint = 'Some text <script>alert(\'gotcha! attack!\')</script> gotcha';
echo $this->Geshi->highlight($dataPrint, 'text');
?>
If oneway has inserted the second example as username, hobby or website it can harm unprotected sites (as soon it is displayed somewhere on the page), as the JS can contain just about anything.

<br /><br />
<h4>Post and Get</h4>
The default cake "/delete method" usually needs some adjustment, as a basic rule $_GET requests should never alter or delete anything (esp. in the DB).
Therefore all deleting requests should be done by $_POST trough some kind of visible or hidden form.<br />
Additionally you would use the security or request component to check inside the method if the request is valid.<br /><br />
<b>Ajax Posts</b>
Same rules - but $_GET could be used for altering something, as it can (and should) be secured by the request component method "isAjax()":
<?php
$dataPrint = 'function deleteSomething() {
	if ($this->RequestHandler->isPost() && $this->RequestHandler->isAjax()) {
		...
	}
}';
echo $this->Geshi->highlight($dataPrint, 'php');
?>
Then you would not use "$this->RequestHandler->isPost()".

<br /><br />
<h4>['] or ["]</h4>
Usually I use the normal ['] - as ["] is for html attributes. <br />
No variables inside strings - they are splitted like that:
<?php
$dataPrint = 'echo \'A string with \'.$someVariable.\' and \'.SOME_CONSTANT.\'!\';
echo \'<a href="example.org" title="\'.$title.\'" alt="there is no alt tag :)" onclick="">Link</a>\';';
echo $this->Geshi->highlight($dataPrint, 'php');
?>
This increases readability most of the time. <br />
The only case I used ["] was inside Query() functions of the database object, where ['] is needed to escape strings.


<br /><br />

<h4 id="db">MYSQL</h3>
All tables start with a primary key (integer), unsigned:
<?php
$dataPrint = '`id` int(10) unsigned NOT NULL';
echo $this->Geshi->highlight($dataPrint, 'mysql');
?>

Same settings for the foreign keys.
<br /><br />
The field names are lowercase - and multiple words seperated through underscores:<br />
some_field_id
<br /><br />
Numeric fields (INT, TINYINT etc) can be "unsigned", if there won't be any negative values, primary and foreign keys must be.

<h4>Usabiltity</h4>
I started to group certain tables.<br />
In my opinion it can be quite a difficult task to keep track of the tables belonging together (especially with using HATBM naming rule "alphabetic order").
So I now group them by the logical "main" table. The main table will be the first word (singular! + [_]) of the other tables.
<br /><br />
Example "role":
<code>
<b>roles</b><br />
<b>role_xyz</b> (roles HasMany xyz)<br />
<b>role_users</b> (HABTM between roles and users)<br />
<b>role_applications</b> (HABTM between roles and users)
</code>
As you can see, here are two relations that have the same 2 tables, all of them can be found under "role...". If you would try to sort them the cake way, you would have a naming conflict as well as (in most cases) difficulties to find them due to different first letters (roles, xyz, ..).
<br /><br />
The naming conflict results out of the convention to have to name both "roles_users".<br />
Both tables have not that much to do which each other though, as the first one consists of the current roles-user-relation, the second one has some information about users applying to roles (with apply_date, approval_status (which could be -1 = dissapproved), approved_by (admin userid) and so on). But still, they all have the
roles table in common. If you look for them it would not be "applications", "applications_roles" or "xyz", but "role_...).
<br /><br />

Example "zendsnippet":
<code>
<b>zendsnippets</b><br />
<b>zendsnippet_cats</b> (zendsnippets HasMany cats)<br />
<b>zendsnippet_elements</b> (zendsnippets HasMany elements)<br />
<b>zendsnippet_tags</b> (HABTM between zendsnippets and tags)
</code>
It is more logical to always group them all under the main table - in this case under "zendsnippets".<br /> Otherwise you find the HATBM table for "zendnippets" under "tags_zendnippets" - but for "perlsnippets" it would be "perlsnippets_tags" - which (quite obvious) is not persuading at all.


<br /><br />

<h4 id="html">HTML</h3>
All tags, attributes and js-functions are lowercase
<br /><br />
Examples:
<?php
$dataPrint = '<a href=""> instead of <A href="">
<body style=""> instead of <BODY STYLE="">
<input onclick="doSomething()" instead of <input Onclick="DoSomething()"';
echo $this->Geshi->highlight($dataPrint, 'text');
?>

Everything should be styled by CSS - avoid inline styling.
If it is used (where it makes sense), it does override the css styling for the current tag - which is sometimes wanted.
<br /><br />

<h4>xhtml reference</h4>
For my list of "should and should'nt use" xhtml tags, take a look <?php echo $this->Html->link('here', array('xhtml-reference'))?>.
<br /><br />

<h4>PHP and HTML in the view</h4>
Use as less inside 'echo' as possible (not parsing unnessary things through it). So this:
<?php
$dataPrint = '<tr <?php echo $class;?>>
		<td>
			<?php echo $this->Html->link($example[\'Example\'][\'title\']); ?>
		</td>
		<td>
			<?php echo $this->Datetime->niceDate($example[\'Example\'][\'modified\']); ?>
		</td>
	</tr>';
echo $this->Geshi->highlight($dataPrint, 'php');
?>
is better than:
<?php

$dataPrint = 'echo \'<tr \'.$class.\'>
		<td>
			\'.$this->Html->link($example[\'Example\'][\'title\']).\'
		</td>
		<td>
			\'.$this->Datetime->niceDate($example[\'Example\'][\'modified\']).\'
		</td>
	</tr>\';';
echo $this->Geshi->highlight($dataPrint, 'php');
?>
And it is better to read anyway, as the view itself is mostly HTML anyway - and a good editor will be able to show missing tags or the nesting structure of code blocks right away this way (the &lt;?..?&gt; content is just treated as normal text. With PHP as dominating syntax this would not be possible.<br />

<?php echo $this->Html->image('content/coding-php-html.jpg', array('class' => 'content'))?><br />
Note the green highlighted "matching start tag" to the clicked on end tag.

<br /><br />

<h4 id="css">CSS</h3>

<h4>Common Things</h4>
<code>
<b>class:</b> .some_class_name<br />
<b>id:</b> #some-id-to-an-element
</code>
both with lowercase characters (although classes are not case-sensitive, id's are!),
class with underscores [_] as seperator, id with minus [-]
<br /><br />
Classes should have (as shown below) the tag-type in front of them (to avoid problems with different tags and same class names).<br/>
For an 'id' it is optional (can make it more transparent and easier to find it in the code later on, though - and there could be two different tags on two different pages with the same ID - which would then get messed up be the other CSS style maybe).
<br /><br />
Examples:
<?php
$dataPrint = '.error_warning {

}
table.list_xyz {
	...
}

#an-id-to-whatever {		/* should be unique on the current page anyway! */
	...
}
#an-id-to-an-inputfield {	/* therefore does not need \'input \' in front of it */
	...
}';
echo $this->Geshi->highlight($dataPrint, 'css');
?>

Do not name the fields after their style, but after the function/meaning - as the style can change and will result in things like ".red { color: yellow;}"
<br /><br />
Bad Examples:
<?php
$dataPrint = 'span.green {
	color: green;
}
div.bold {
	font-weight: bold;
}';
echo $this->Geshi->highlight($dataPrint, 'css');
?>

Good Examples:
<?php
$dataPrint = 'span.success {
	/* color: green; // not any more */
	color: dark-green;
}
div.important {
	/* font-weight: bold; // not any more */
	font-size: 14px;
}';
echo $this->Geshi->highlight($dataPrint, 'css');
?>

Use Inheritance. And - to avoid, that any added style for other pages affect the current one, declare the inheritance the following way:
<br /><br />
Example:
<?php
$dataPrint = 'div.report {
	margin-left:12px;
	background-color:#E6E7E8;
}
div.report table {
	width:330px;
	border-collapse: separate;
}
div.report table th {
	font-weight: bold;
}
div.report table td.descr {
	text-align: center;
}';
echo $this->Geshi->highlight($dataPrint, 'css');
?>

Now, the .descr class on the td of this table does not get screwed up, if there is any other .descr in the stylesheets used








<br /><br />

<h4 id="js">JS</h3>
Naming Convention for the functions as for PHP (camelBack):
<?php
$dataPrint = 'function someJavascriptFunction(var) {
	...
}';
echo $this->Geshi->highlight($dataPrint, 'javascript');
?>

<h4>Cross Browser Functionality</h4>
Even if IE makes our life not that easy sometimes what this concerns...
<br /><br />
Dont use old JS-Code anymore:<br />
instead of document.form or document.all it is now documentGetElementById (and sometimes ByTagName).
<br /><br />
As document.getElementById('') (and the reference to an id) is case-sensitive, always use small id html attributes.
<br /><br />
Example:
<?php
$dataPrint = 'document.getElementById(\'xyz\').value=\'123\';

...

<input id="xyz"/>';
echo $this->Geshi->highlight($dataPrint, 'javascript');
?>


All style attributes have to be lowercase as well:
<?php
$dataPrint = '/* would not work properly: */
document.getElementById(\'xyz\').style[\'Color\']=\'black\';

/* it has to be: */
document.getElementById(\'xyz\').style[\'color\']=\'black\';';
echo $this->Geshi->highlight($dataPrint, 'javascript');
?>


<h4>Jquery</h4>
I use the Jqery Library 1.2.6<br />
This is an extandable and very handy to use library which can cut down code length dramatically. And it makes it easier to program
for several browsers, as it handles the different syntaxes itself.


<br /><br />

<h4 id="doc">Documentation</h3>
Especially inline-documentation is quite important. Not only for others, but also for yourself to be able to understand what you did some months/years ago.<br /><br />

<h4>PHP</h4>
All comments should be written in English, and should in a clear way describe what is going on.
Use <b>/* */</b> for longer comments and <b>#</b> for one-line comments (usually right above the code). <br />
I do not recommand // as it usually stands for some line of code commented out - which can lead to missunderstandings.<br /><br />

You should also comment all your methods/functions - take a look at the <?php echo $this->Html->link('different tags', 'https://trac.cakephp.org/wiki/Developement/CodingStandards#Commentingcode', array('target' => '_blank'))?> availible.
<br /><br />
Examples:
<?php
$dataPrint = '# now we need to shift the array as they need to be displayed in reverse order
$var = array_shift($var);

...


/**
	* @param string $var Dirty name.
	* @return string Cleaned name.
	*/
function someFunction($var) {

}';
echo $this->Geshi->highlight($dataPrint, 'php');
?>
With a good php editor (like PHPDesigner 200x), you are able to use these method comments while programming (as it pops up on each method/function as soon as you click inside the brackets (even beyond the current page - as the editor looks through your complete app directory):<br />
<?php echo $this->Html->image('content/commenting-php.jpg', array('class' => 'content'))?>


<br /><br />
<h4>CSS</h4>
In CSS documents there is just the <b>/* */</b> to comment your code (usually above).<br />
You should built some kind of hierarchy with them - if you have any.<br /><br />
Lets say we have a global css file (default site wide css) and another "specific controller" css file.<br />
Then you could sort them like this (the action level is not really necessary, but shows what could be done):
<?php
$dataPrint = '/***** POSTS CONTROLLER *****/

/*** View Action ***/

div.report {
	margin-left:12px;
	background-color:#E6E7E8;
}

/* the table for the reports */
div.report table {
	width:330px;
	border-collapse: separate;
}
div.report table td.descr {
	text-align: center;
}

...';
echo $this->Geshi->highlight($dataPrint, 'css');
?>
This way you find the corresponding css styles in a sec.

<br /><br />
<h4>JS</h4>
Use <b>/* */</b> for comments (usually above the code)
<?php
$dataPrint = '/* would not work properly: */
document.getElementById(\'xyz\').style[\'Color\']=\'black\';';
echo $this->Geshi->highlight($dataPrint, 'javascript');
?>
Although // would be allowed as well - this is (as in PHP) usually code commented out.


<br /><br />
<h4>HTML</h4>
Usually you do not comment that much in HTML files (in the view files you would use PHP and its commenting syntax), but you could use <b>&lt;!--  --&gt;</b> for comments (usually inline).<br />
Be aware that only PHP comments are hidden from the user, all other ones can be accessed easily through the browsers source code.


<br /><br />
<h4 id="principles">Cake Principles</h3>
Most of it can be found on the cake website. But a short review:<br />

<h4>Common things</h4>
as less as possible HTML inside the controller, its better to pass the array/string to the view (or render en element), which then puts it into the appropriate design.

<br /><br />
<h4>Fat models, thin controllers</h4>
You may leave all kinds of find() methods inside the model, especially if they get more complicated (sorting, certain fields only, limits etc) and just calling this model method from the controller. This way, you can easily adjust (e.g.)<br />
- the limit from 20 to 30, <br />
- sorting order from id ASC to sort ASC<br />
- wanted fields<br />
- ...<br />
and it will affect all corresponding controllers

<?php if (false) { ?>
<br /><br />
Example:
<?php

?>
<?php } ?>

<br /><br />
<h3>Anything missing?</h3>
Did i forget anything - or are there some things you do not agree on? Let me know.