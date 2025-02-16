<?php
/**
 * @var \App\View\AppView $this
 * @var array $envVars
 * @var array $localConfig
 */
?>
<div class="columns col-md-12">

	<h1>ENV config</h1>

	<table class="table">
		<tr>
			<th>ENV</th><th>Value defined</th>
		</tr>
		<?php foreach ($envVars as $envVar => $value) { ?>
			<tr>
				<td>
					<?php echo h($envVar); ?>
				</td>
				<td>
					<?= $this->element('Setup.ok', ['value' => $this->element('Setup.yes_no', ['value' => $value !== false]), 'ok' => $value !== false, 'escape' => false]) ?>
				</td>
			</tr>
		<?php } ?>
	</table>


	<br />
	<h2>Dynamic Configs</h2>

	<code>app_local.php</code>: <?= $this->element('Setup.yes_no', ['value' => $localConfig !== null]) ?>

	<h3>Defined config keys</h3>
	<?php
	if ($localConfig) {
		$this->loadHelper('Tools.Tree');

		$callback = function ($node) {
			if (!$node['hasChildren'] && empty($node['children']) && $node['data']['value'] === []) {
				return '';
			}

			$name = h($node['data']['name']);
			if ($node['hasChildren'] || !empty($node['children'])) {
				return $name;
			}

			$hasValue = $node['data']['value'] !== null;
			$value = '';
			if ($hasValue) {
				if (!is_string($node['data']['value']) || $node['data']['value'] === '') {
					$value = \Cake\Error\Debugger::exportVar($node['data']['value'], 1);
				} else {
					$value = '(string)...';
				}
			}

			return $name . ' ' . ($hasValue ? $this->element('Setup.yes_no', ['value' => $hasValue]) . ' <code>' . $value .'</code>' : '<code>null</code>');
		};

		echo $this->Tree->generate($localConfig, ['callback' => $callback]);
	} else {
		echo '<i>n/a</i>';
	}
	?>
</div>
