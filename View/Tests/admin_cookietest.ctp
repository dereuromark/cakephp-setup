<h2><?php echo __('Cookie Tests');?></h2>
<?php echo $this->Html->link('Complete Reset (Cookie/Session)', array('destroy' => 1))?> | <?php echo $this->Html->link('Session Reset', array('reset' => 1))?>


<br />
<h3>dfdf</h3>
<?php echo $this->Form->create('Configuration');?>
	<fieldset>
		<legend><?php echo __('Count Up');?></legend>
	<?php
		echo $this->Form->input('value', array());
		echo $this->Form->input('save_to_session', array('type' => 'checkbox'));
		echo $this->Form->input('save_to_cookie', array('type' => 'checkbox'));
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>



<h3>Current Content</h3>
<p>
Session:
<ul><?php
if (isset($sessionContent)) {
	echo pre($sessionContent);
} else {
	echo '---';
}
?>
</ul>
</p>
<br />

<p>
Cookie:
<ul><?php
if (isset($cookieContent)) {
	echo pre($cookieContent);
} else {
	echo '---';
}
?>
</ul>
</p>

<!--
<br />

<p>

FullCookieContent (not necessary usually):
<ul><?php
echo pre($cookieFullContent);
?>
</ul>
</p>
 -->

<h3>Results</h3>
<b>Generell</b>
<ul>
<li>Prinzipiell entspricht Verhalten bei blockierten Cookies meinen Erwartungen</li>
<li>Seltsames Verhalten in manchen Browsern... Einige konnt ich noch nich testen</li>
</ul>

<b>Browser-Spezifisch</b>
<ul>
<li>IE6: <br />
	---
</li>

<li>IE7: <br />
	---
</li>

<li>IE8: <br />
	<i>an..</i> OK<br />
	<i>aus..</i> OK (Session bleibt erhalten)
</li>

<li>FF3: <br />
	<i>an..</i> OK<br />
	<i>aus..</i> Ganz böse: Login etc funzt nicht mehr (!!!)
</li>

<li>Opera9: <br />
	<i>an..</i> OK<br />
	<i>aus..</i> Ganz böse: Login etc funzt nicht mehr (!!!)
</li>

<li>Chrome1/Chrome2: <br />
	<i>an..</i> OK<br />
	<i>aus..</i> Ganz böse: Login etc funzt nicht mehr (!!!)
</li>
</ul>

Ich vermute seltsames Verhalten seitens Cake CookieComponent, da die Cookies sehr wohl noch über die Browser-Optionen sichtbar und einsehbar sind....
<br />
Die Cake-Session braucht nämlich die Cookies zum Funktionieren, habe ich herausgefunden
<br /><br />
Einzig der IE8 funzt weiter, weil er - wie schon immer - nicht standardkonformes Verhalten zeigt, und die Cake-Session trotzdem irgendwie in ein Cookie zwängt!