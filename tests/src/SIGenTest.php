<?php

/**
 * @package    SIGen
 * @subpackage Exception
 * @author     Alexander Wühr <lx@boolshit.de>
 * @copyright  2013 Alexander Wühr <lx@boolshit.de>
 * @license    http://opensource.org/licenses/MIT  The MIT License (MIT)
 * @link       https://boolshit.de
 */

namespace Lx\SIGen;

require_once __DIR__.'/../../src/SIGen.php';

class SIGenTest extends \PHPUnit_Framework_TestCase {

	public function classNameProvider() {
		return array(
			array('\Lx\SIGen\InterfaceBuilder'), array('\Lx\SIGen\Proxy\SimpleProxy'),
			array('\Lx\SIGen\Exception\Exception'), array('\Lx\SIGen\Exception\EvalException'),
			array('\Lx\SIGen\Exception\InvalidArgumentException'),
			array('\Lx\SIGen\Exception\RequirementException'),
		);
	}

	/**
	 * @test
	 * @testdox All SIGen classes are available
	 * @dataProvider classNameProvider
	 */
	public function testClassLoader($classname) {
		$this->assertTrue(class_exists($classname, true));
	}
}
