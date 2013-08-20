<?php
App::uses('OpCodeCacheLib', 'Setup.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class OpCodeCacheLibTest extends MyCakeTestCase {

	/**
	 * test the postal method of DeValidation
	 *
	 * @return void
	 */
	public function testDetect() {
		$is = OpCodeCacheLib::detect();
		$this->debug($is);
		$this->assertTrue(is_array($is) && !empty($is));

		$is = OpCodeCacheLib::detect('apc');
		$this->debug($is);
		$this->assertFalse($is);

		$is = OpCodeCacheLib::detect('xyz');
		$this->assertTrue($is === null);
	}

}
