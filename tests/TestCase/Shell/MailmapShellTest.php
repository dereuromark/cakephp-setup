<?php

namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Setup\Shell\MailmapShell;
use Shim\TestSuite\ConsoleOutput;

class MailmapShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\MailmapShell
	 */
	protected $Shell;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMockBuilder(MailmapShell::class)
			->onlyMethods(['in', 'err', '_stop', 'runGitCommand'])
			->setConstructorArgs([$io])
			->getMock();

		$array = [
			'11 foo <foo@bar.de>',
			' 2 fooo <fOo@BaR.dE>',
		];
		$this->Shell->expects($this->any())->method('runGitCommand')->willReturn($array);

		if (is_file(TMP . '.mailmap')) {
			unlink(TMP . '.mailmap');
		}
	}

	/**
	 * @return void
	 */
	public function testGenerate() {
		$this->Shell->runCommand(['generate', TMP]);
		$output = $this->out->output();

		$this->assertStringContainsString('Found 0 existing entries in .mailmap file', $output);
		$this->assertStringContainsString('Found 2 shortlog history lines', $output);
		$this->assertStringContainsString('2 additional rows written to', $output);

		$content = file_get_contents(TMP . '.mailmap');
		$this->assertStringContainsString('foo <foo@bar.de> fooo <fOo@BaR.dE>', $content);
	}

}
