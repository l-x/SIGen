<?php

/**
 * @package    SIGen
 * @subpackage Proxy
 * @author     Alexander Wühr <lx@boolshit.de>
 * @copyright  2013 Alexander Wühr <lx@boolshit.de>
 * @license    http://opensource.org/licenses/MIT  The MIT License (MIT)
 * @link       https://boolshit.de
 */

namespace Lx\SIGen\Proxy;
use Lx\SIGen;

require_once __DIR__.'/../../src/autoload.php';

class SimpleProxyProxy extends SimpleProxy {

	public function proxyGet($name) {
		return $this->$name;
	}

	public function proxySet($name, $value) {
		$this->$name = $value;
	}

	public function proxyCall($name, $arguments) {
		return call_user_func_array(array($this, $name), $arguments);
	}
}


class SimpleProxyTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @test
	 * @testdox __construct throws exception when argument is not an object
	 * @expectedException \Lx\SIGen\Exception\InvalidArgumentException
	 * @covers \Lx\SIGen\Proxy\SimpleProxy::__construct
	 */
	public function constructThrowsExceptionOnInvalidArgument() {
		new SimpleProxyProxy('not an object');
	}

	/**
	 * @test
	 * @testdox __construct sets instance member properly
	 * @covers \Lx\SIGen\Proxy\SimpleProxy::__construct
	 */
	public function constructSetsMember() {
		$mock_object = new \stdClass();
		$object = new SimpleProxyProxy($mock_object);
		$this->assertEquals($mock_object, $object->proxyGet('_service_template'));
	}

	/**
	 * @test
	 * @testdox call calls service object's method properly
	 * @covers \Lx\SIGen\Proxy\SimpleProxy::call
	 * @fixme Isolate preProcess and postProcess to avoid implicit tests of this methods
	 */
	public function callCallsCorrectMethod() {
		$expected = array('test_argument1', 'test_argument2');
		$mock_object = $this->getMock('\stdClass', array('test_method'));
		$mock_object->expects($this->once())->method('test_method')->with($expected[0], $expected[1])
			->will($this->returnValue(array(
			                               $expected[0], $expected[1]
			                          )));
		$object = new SimpleProxyProxy(new \stdClass());
		$object->proxySet('_service_template', $mock_object);

		$this->assertEquals($expected, $object->proxyCall('call', array('test_method', $expected)));

	}

	/**
	 * @test
	 * @testdox preProcess returns unchanged method and arguments
	 * @covers \Lx\SIGen\Proxy\SimpleProxy::preProcess
	 */
	public function preProcessReturnsUnchanged() {
		$method = 'testmethod';
		$arguments = array('some', 'useless', 'arguments');
		$object = new SimpleProxyProxy(new \stdClass());

		list($returned_method, $returned_arguments) = $object->proxyCall('preProcess', array($method, $arguments));
		$this->assertEquals($method, $returned_method);
		$this->assertEquals($arguments, $returned_arguments);
	}

	/**
	 * @test
	 * @testdox postProcess returns unchanged result
	 * @covers \Lx\SIGen\Proxy\SimpleProxy::postProcess
	 */
	public function postProcessReturnsUnchanged() {
		$result = array('some', 'useless', 'arguments');
		$object = new SimpleProxyProxy(new \stdClass());

		$returned_result = $object->proxyCall('postProcess', array('dummy', $result));
		$this->assertEquals($result, $returned_result);
	}
}
