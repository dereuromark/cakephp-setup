<?php
App::uses('DebugLib', 'Setup.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class DebugLibTest extends MyCakeTestCase {

	public $DebugLib = null;

	public function setUp() {
		parent::setUp();

		$this->DebugLib = new DebugLib();
	}

	public function testReturnInBytes() {
		$res = $this->DebugLib->returnInBytes('128M');
		$this->debug($res);
		$this->assertTrue(is_int($res) && $res == 134217728);
	}

	/**
	 * test
	 */
	public function testMemoryUsage() {
		$res = $this->DebugLib->memoryUsage();
		$this->debug($res);
		$this->assertTrue(is_int($res) && $res > 1000000 && $res < 90000000);

		$res2 = $this->DebugLib->peakMemoryUsage();
		$this->debug($res2);
		$this->assertTrue(is_int($res2) && $res2 > 1000000 && $res2 < 90000000 && $res2 > $res);

		$res = $this->DebugLib->memoryLimit();
		$this->debug($res);
		$this->assertTrue(strpos($res, 'M') !== false && (int)$res >= 32 && (int)$res <= 512);

		$res = $this->DebugLib->memoryLimitAdjustable();
		$this->debug($res);
		$this->assertTrue($res);
	}

	public function testConfigVar() {
		$res = $this->DebugLib->configVar('error_reporting');
		$this->debug($res);
		$this->assertTrue(!empty($res) && $res >= 0);
	}

	public function testDifferenceRuntimeConfig() {
		$res = $this->DebugLib->configVar('memory_limit');
		$this->debug($res);
		$this->assertTrue(!empty($res) && $res >= 0);

		$res = $this->DebugLib->runtimeVar('memory_limit');
		$this->debug($res);
		$this->assertTrue(!empty($res) && $res >= 0);

		// change at runtime
		$newValue = ((int)$res) * 2;
		$this->debug($newValue);
		ini_set('memory_limit', $newValue . 'M');

		// NOT WORKING!!! changable in PHP_INI_ALL?
		$res2 = $this->DebugLib->configVar('memory_limit');
		$this->debug($res2);
		$this->assertTrue(!empty($res2) && $res2 >= 0);

		$res2 = $this->DebugLib->runtimeVar('memory_limit');
		$this->debug($res);
		$this->assertTrue(!empty($res2) && $res2 >= 0);

		// post_max_size and upload_max_filesize
	}

	public function testDns() {
		$res = $this->DebugLib->getmxrrAvailable();
		$this->skipIf(!$res);

		$res = getmxrr('web.de', $hosts, $weight);
		$this->assertTrue($res);
		$this->assertTrue(!empty($hosts));
		$this->assertTrue(!empty($weight));

		$res = $this->DebugLib->checkdnsrrAvailable();
		$this->skipIf(!$res);

		$res = checkdnsrr('web.de', 'mx');
		$this->assertTrue($res);
	}

	public function testSettings() {
		$res = $this->DebugLib->execAllowed();
		$this->debug($res);
		$this->assertTrue($res === true || $res === false);

		$res = $this->DebugLib->magicQuotesGpc();
		$this->debug($res);
		$this->assertTrue($res === true || $res === false);

		$res = $this->DebugLib->registerGlobals();
		$this->debug($res);
		$this->assertTrue($res === true || $res === false);

		$res = $this->DebugLib->displayErrors();
		$this->debug($res);
		$this->assertTrue($res === true || $res === false);

		$res = $this->DebugLib->fileUpload();
		$this->debug($res);
		$this->assertTrue($res);

		$res = $this->DebugLib->postMaxSize();
		$this->debug($res);
		$this->assertTrue(!empty($res) && (int)$res >= 2 && (int)$res <= 512);

		$res = $this->DebugLib->uploadMaxSize();
		$this->debug($res);
		$this->assertTrue(!empty($res) && (int)$res >= 2 && (int)$res <= 512);

		$res = $this->DebugLib->allowUrlFopen();
		$this->debug($res);
		$this->assertTrue($res === true || $res === false);

		$res = $this->DebugLib->shortOpenTag();
		$this->debug($res);
		$this->assertTrue($res === true || $res === false);

		$res = $this->DebugLib->safeMode();
		$this->debug($res);
		$this->assertFalse($res);
	}

	public function testPhpVersion() {
		$res = $this->DebugLib->phpVersion();
		$this->debug($res);
		$this->assertTrue($res > 4 && $res < 7);

		$res = $this->DebugLib->serverSoftware();
		$this->debug($res);
		$this->assertTrue(!empty($res) && (!WINDOWS || strpos($res, 'PHP') !== false));
	}

	public function testTime() {
		$res = $this->DebugLib->phpTime();
		$this->debug($res);
		$this->assertEquals(date(FORMAT_DB_DATETIME), $res);

		$res = $this->DebugLib->phpUptime();
		$this->debug($res);
		if (WINDOWS) {
			$this->assertTrue(empty($res));
		} else {
			$this->assertTrue(!empty($res));
		}

		$res = $this->DebugLib->maxExecTime();
		$this->debug($res);
		$this->assertTrue(is_int($res) && $res >= 0);

		$res = $this->DebugLib->maxInputTime();
		$this->debug($res);
		$this->assertTrue(is_int($res) && (!WINDOWS || $res >= 0));
	}

	public function testIni() {
		$res = $this->DebugLib->mbStringOverload();
		$this->debug($res);
		$this->assertFalse($res);

		$res = $this->DebugLib->mbDefLang();
		$this->debug($res);
		$this->assertTrue($res);

		$res = $this->DebugLib->loadedExtensions();
		$this->debug($res);
		$this->assertTrue(!empty($res) && count($res) > 10);

		$res = $this->DebugLib->extensionLoaded('date');
		$this->assertTrue($res);

		$res = $this->DebugLib->extensionLoaded('xyz');
		$this->assertFalse($res);

		$res = $this->DebugLib->extensionFunctions('xml');
		$this->debug($res);
		$this->assertTrue(!empty($res) && count($res) > 10);

		$res = $this->DebugLib->extensionFunctions('xyz');
		$this->debug($res);
		$this->assertFalse($res);

		$res = $this->DebugLib->openBasedir();
		$this->debug($res);
		$this->assertTrue(is_array($res) && empty($res));
	}

	public function testUptime() {
		$res = $this->DebugLib->getUptime();
		$this->debug($res);
		$this->skipIf(WINDOWS, 'Not working on windows');

		$this->debug(FORMAT_NICE_YMDHMS, $res['timestamp']);
		$this->assertTrue(is_array($res) && !empty($res));
	}

	public function testServerLoad() {
		$this->skipIf(WINDOWS, 'Not working on windows');

		$res = $this->DebugLib->getServerLoad();
		$this->debug($res);
		$this->assertTrue(is_array($res) && !empty($res));
	}

	public function testKernelVersion() {
		$res = $this->DebugLib->getKernelVersion();
		$this->debug($res);
		$this->assertTrue(is_string($res) && !empty($res));
	}

	public function testCpu() {
		$this->skipIf(WINDOWS, 'Not working on windows');

		$res = $this->DebugLib->getCpu();
		$this->debug($res);
		$this->assertTrue(is_array($res) && !empty($res));
	}

	public function testDbStuff() {
		$this->skipIf(true, 'TODO');

		$res = $this->DebugLib->dbClientEncoding();
		$this->debug($res);
		$this->assertNull($res);
	}

}
