<?php

namespace Setup\Test\TestCase\View;

use Bake\CodeGen\FileBuilder;
use Bake\Utility\TemplateRenderer;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\TestSuite\TestCase;
use Setup\Test\Generated\BakeGuardRegression;
use Setup\Test\Generated\Command\BakeNativeRegressionCommand;

class BakeTemplatesTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		$this->loadPlugins(['Bake', 'Setup']);
	}

	public function testGeneratedEntityEnforcesPatchableAndHiddenFields(): void {
		$renderer = new TemplateRenderer('Setup');
		$renderer->set([
			'name' => 'BakeGuardRegression',
			'fields' => ['id', 'title', 'secret'],
			'primaryKey' => ['id'],
			'hidden' => ['secret'],
			'propertySchema' => [],
			'fileBuilder' => new FileBuilder(new ConsoleIo(new StubConsoleOutput()), 'Setup\\Test\\Generated'),
		]);
		$renderer->getView()->loadHelper('SetupBake', ['className' => 'Setup.SetupBake']);
		$source = $renderer->generate('Bake.Model/entity');
		$this->assertStringContainsString('protected array $patchable', $source);
		$this->assertStringContainsString('protected array $hidden', $source);
		$this->loadGeneratedClass($source);
		$entity = new BakeGuardRegression(['id' => 7, 'title' => 'Allowed', 'secret' => 'Hidden'], ['guard' => true]);
		$this->assertNull($entity->get('id'));
		$this->assertSame('Allowed', $entity->get('title'));
		$this->assertArrayNotHasKey('secret', $entity->toArray());
	}

	public function testGeneratedCommandLoadsAndRunsOnCakePhpSix(): void {
		$renderer = new TemplateRenderer('Setup');
		$source = $renderer->generate('Bake.Command/command', [
			'name' => 'BakeNativeRegression',
			'namespace' => 'Setup\\Test\\Generated',
		]);
		$this->assertStringContainsString('public function execute()', $source);
		$this->loadGeneratedClass($source);
		$command = new BakeNativeRegressionCommand();
		$this->assertNull($command->run([], new ConsoleIo(new StubConsoleOutput())));
	}

	private function loadGeneratedClass(string $source): void {
		$path = tempnam(TMP, 'bake_native_');
		file_put_contents($path, $source);
		try {
			require $path;
		} finally {
			unlink($path);
		}
	}

}
