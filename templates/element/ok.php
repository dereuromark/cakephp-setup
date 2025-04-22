<?php
/**
 * Overwrite this element snippet locally to customize if needed.
 *
 * @var \App\View\AppView $this
 * @var string $value
 * @var bool $ok
 * @var bool|null $escape
 */
if (!isset($escape)) {
	$escape = true;
}
?>
<?php
if ($this->helpers()->has('Templating')) {
	echo $this->Templating->ok($value, $ok, ['escape' => $escape]);
} elseif ($this->helpers()->has('Format')) {
	echo $this->Format->ok($value, $ok);
} else {
	echo $ok ? '<span class="yes-no yes-no-yes">' . ($escape ? h($value) : $value) . '</span>' : '<span class="yes-no yes-no-no">' . ($escape ? h($value) : $value) . '</span>';
}
?>
