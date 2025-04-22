<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Http\Cookie\CookieCollection $cookies
 */

use Cake\I18n\DateTime;

?>
<div class="columns col-md-12">

<h1>Cookies</h1>

<?php
/** @var \Cake\Http\Cookie\Cookie $cookie */
foreach ($cookies->getIterator() as $cookie) {
	$expireDateTime = $cookie->getExpiry();

	echo '<h3>' . $cookie->getName() . '</h3>';

	echo '<pre>';
	echo json_encode($cookie->toArray(), JSON_PRETTY_PRINT);
	echo '</pre>';

	echo '<p>';
	echo 'Expires: ' . ($expireDateTime ? $this->Time->nice($expireDateTime) : 'n/a');
	echo '</p>';
}

?>

</div>
