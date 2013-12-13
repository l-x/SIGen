<?php

/**
 * @package    SIGen
 * @author     Alexander Wühr <lx@boolshit.de>
 * @copyright  2013 Alexander Wühr <lx@boolshit.de>
 * @license    http://opensource.org/licenses/MIT  The MIT License (MIT)
 * @link       https://boolshit.de
 */

namespace Lx\SIGen;
use Lx\SIGen;
use Lx\SIGen\Exception;

require_once __DIR__.'/../../../../src/Lx/SIGen.php';

class InterfaceBuilderProxy extends InterfaceBuilder {

	public function proxyGet($name) {
		return $this->$name;
	}

	public function proxyGetStatic($name) {
		return static::$$name;
	}

	public function proxySet($name, $value) {
		$this->$name = $value;
	}

	public function proxySetStatic($name, $value) {
		static::$$name = $value;
	}

	public function proxyCall($name, $arguments) {
		return call_user_func_array(array($this, $name), $arguments);
	}
}

class InterfaceBuilderTest extends \PHPUnit_Framework_TestCase {

	protected $object = null;

	public function setUp() {
		$this->object = new InterfaceBuilderProxy();
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox __construct invokes setContext
	 * @covers \Lx\SIGen\InterfaceBuilder::__construct
	 */
	public function constructorSetsContextAndParentClass() {
		$context = 'context';
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilder', array('setContext'));
		$sut->expects($this->once())->method('setContext')->with($context);
		$sut->__construct($context);
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox getProxyParentClass returns proper value
	 * @covers \Lx\SIGen\InterfaceBuilder::getProxyParentClass
	 */
	public function getProxyParentClass() {
		$this->object->proxySet('_proxy_parent_class', 'some value');
		$this->assertEquals('some value', $this->object->getProxyParentClass());
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox getProxyParentClass returns proper value
	 * @covers \Lx\SIGen\InterfaceBuilder::getProxyParentClass
	 */
	public function getProxyParentClassReturnsDefaultOnNull() {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('getDefaultProxyParentClass'), array(), '', false);
		$sut->staticExpects($this->once())->method('getDefaultProxyParentClass')
			->will($this->returnValue('default_class'));

		$sut->proxySet('_proxy_parent_class', null);
		$this->assertEquals('default_class', $sut->getProxyParentClass());
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _validateParentProxyClass throws exception when argument is not a class
	 * @expectedException \Lx\SIGen\Exception\InvalidArgumentException
	 * @covers \Lx\SIGen\InterfaceBuilder::_validateParentProxyClass
	 */
	public function validateParentProxyClassThrowsExceptionOnNoClass() {
		$this->object->proxyCall('_validateParentProxyClass', array('non_existing_class'));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _validateParentProxyClass throws exception when class does not have a method named 'call'
	 * @expectedException \Lx\SIGen\Exception\InvalidArgumentException
	 * @covers \Lx\SIGen\InterfaceBuilder::_validateParentProxyClass
	 */
	public function validateParentProxyClassThrowsExceptionOnNoCallMethod() {
		$this->object->proxyCall('_validateParentProxyClass', array('\stdClass'));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _validateParentProxyClass returns true on successfull validation
	 * @covers \Lx\SIGen\InterfaceBuilder::_validateParentProxyClass
	 *
	 */
	public function validateParentProxyClassReturnsTrueOnSuccess() {
		$this->assertTrue($this->object->proxyCall('_validateParentProxyClass', array('\Lx\SIGen\Proxy\SimpleProxy')));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox getDefaultParentProxyClass returns correct value
	 * @covers \Lx\SIGen\InterfaceBuilder::getDefaultProxyParentClass
	 */
	public function getDefaultParentProxyClassReturnsCorrectValue() {
		$backup = $this->object->proxyGetStatic('_default_proxy_parent_class');
		$this->object->proxySetStatic('_default_proxy_parent_class', 'test_class');

		$this->assertEquals('test_class', $this->object->getDefaultProxyParentClass());

		$this->object->proxySetStatic('_default_proxy_parent_class', $backup);

	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox getDefaultParentProxyClass sets correct value
	 * @covers \Lx\SIGen\InterfaceBuilder::setDefaultProxyParentClass
	 */
	public function setDefaultParentProxyClassSetsCorrectValue() {
		$proxy_class = 'classname';

		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_validateParentProxyClass'), array(), '', false);
		$sut->staticExpects($this->once())->method('_validateParentProxyClass')->with($proxy_class)
			->will($this->returnValue(true));

		$backup = $sut->proxyGetStatic('_default_proxy_parent_class');

		$sut->setDefaultProxyParentClass($proxy_class);
		$this->assertEquals($proxy_class, $sut->proxyGetStatic('_default_proxy_parent_class'));

		$sut->proxySetStatic('_default_proxy_parent_class', $backup);
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox setProxyParentClass returns current instance
	 * @covers \Lx\SIGen\InterfaceBuilder::setProxyParentClass
	 */
	public function setProxyParentClassReturnsInstance() {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilder', array('_validateParentProxyClass'), array(), '', false);
		$sut->staticExpects($this->once())->method('_validateParentProxyClass')->with('classname')
			->will($this->returnValue(true));
		$this->assertEquals($sut, $sut->setProxyParentClass('classname'));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox setProxyParentClass sets the correct value to the correct variable
	 * @covers \Lx\SIGen\InterfaceBuilder::setProxyParentClass
	 */
	public function setProxyParentClassSetsVar() {
		$this->object->setProxyParentClass('\Lx\SIGen\Proxy\SimpleProxy');
		$this->assertEquals('\Lx\SIGen\Proxy\SimpleProxy', $this->object->proxyGet('_proxy_parent_class'));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox setProxyParentClass accepts null as argument
	 * @covers \Lx\SIGen\InterfaceBuilder::setProxyParentClass
	 */
	public function setProxyParentClassAcceptsNull() {
		$this->object->setProxyParentClass(null);
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox setContext returns current class instance
	 * @covers \Lx\SIGen\InterfaceBuilder::setContext
	 */
	public function setContextReturnsInstance() {
		$this->assertEquals($this->object, $this->object->setContext(array()));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox setContext throws exception when argument is not an array or instance of \ArrayObject
	 * @expectedException  \Lx\SIGen\Exception\InvalidArgumentException
	 * @covers \Lx\SIGen\InterfaceBuilder::setContext
	 */
	public function setContextExceptionOnInvalidArgument() {
		$this->object->setContext('test');
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox setContext sets member to instance of \ArrayObject
	 * @covers \Lx\SIGen\InterfaceBuilder::setContext
	 */
	public function setContextSetsArrayObject() {
		$this->object->setContext(new \ArrayObject());
		$this->assertInstanceOf('\ArrayObject', $this->object->proxyGet('_context'));
		$this->object->setContext(array());
		$this->assertInstanceOf('\ArrayObject', $this->object->proxyGet('_context'));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox setContext properly converts array into \ArrayObject
	 * @covers \Lx\SIGen\InterfaceBuilder::setContext
	 */
	public function setContextSetsProperValues() {
		$this->object->setContext(array('key' => 'value'));
		$context = $this->object->proxyGet('_context');
		$this->assertEquals('value', $context->offsetGet('key'));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox getContext returns correct value
	 * @covers \Lx\SIGen\InterfaceBuilder::getContext
	 */
	public function getContextReturnsCorrectValue() {
		$this->object->proxySet('_context', 'dummy');
		$this->assertEquals('dummy', $this->object->getContext());
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _formatExpression works correct
	 * @covers \Lx\SIGen\InterfaceBuilder::_formatExpression
	 */
	public function formatExpressionWorksCorrect() {
		$this->assertEquals('sometest;', $this->object->proxyCall('_formatExpression', array(
		                                                                                    'sometest', false
		                                                                               )));
		$this->assertEquals('$return_value = sometest;', $this->object->proxyCall('_formatExpression', array(
		                                                                                                    'sometest',
		                                                                                                    true
		                                                                                               )));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox getVariableIdentifier returns correct value
	 * @covers \Lx\SIGen\InterfaceBuilder::getVariableIdentifier
	 */
	public function getVariableIdentifier() {
		$backup = $this->object->proxyGetStatic('_variable_format');
		$this->object->proxySetStatic('_variable_format', 'one%sidentifier');
		$this->assertEquals('one%sidentifier', $this->object->getVariableIdentifier());
		$this->object->proxySetStatic('_variable_format', $backup);
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _eval throws EvalException on eval exception
	 * @covers \Lx\SIGen\InterfaceBuilder::_eval
	 * @expectedException \InvalidArgumentException
	 */
	public function evalThrowsExceptionOnEvalException() {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_formatExpression', 'getContext'));
		$sut->expects($this->once())->method('_formatExpression')
			->will($this->returnValue('throw new \\InvalidArgumentException;'));
		$sut->expects($this->once())->method('getContext')->will($this->returnValue(new \ArrayObject()));

		$sut->proxyCall('_eval', array(null));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _eval throws EvalException on php fatal errors
	 * @covers \Lx\SIGen\InterfaceBuilder::_eval
	 * @expectedException \Lx\SIGen\Exception\EvalException
	 */
	public function evalThrowsExceptionOnPhpFatalError() {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_formatExpression', 'getContext'));
		$sut->expects($this->once())->method('_formatExpression')->with($this->anything())
			->will($this->returnArgument(0));
		$sut->expects($this->once())->method('getContext')->will($this->returnValue(new \ArrayObject()));

		$sut->proxyCall('_eval', array('some really borken stuff which will end in a $ fatal ? errÖr'));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _eval throws EvalException on php errors
	 * @covers \Lx\SIGen\InterfaceBuilder::_eval
	 * @expectedException \Lx\SIGen\Exception\EvalException
	 */
	public function evalThrowsExceptionOnPhpError() {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_formatExpression', 'getContext'));
		$sut->expects($this->once())->method('_formatExpression')->with($this->anything())
			->will($this->returnArgument(0));
		$sut->expects($this->once())->method('getContext')->will($this->returnValue(new \ArrayObject()));

		$sut->proxyCall('_eval', array('$value = constant_expected;'));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _eval evaluates expression properly
	 * @covers \Lx\SIGen\InterfaceBuilder::_eval
	 */
	public function evalEvaluatesExpressionProperly() {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_formatExpression', 'getContext'));
		$sut->expects($this->once())->method('_formatExpression')->with('expression', 'return')
			->will($this->returnValue('function test_dummy() {};'));
		$sut->expects($this->once())->method('getContext')->will($this->returnValue(new \ArrayObject()));

		$sut->proxyCall('_eval', array('expression', 'return'));
		$this->assertTrue(function_exists('test_dummy'));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _eval return expression result
	 * @covers \Lx\SIGen\InterfaceBuilder::_eval
	 */
	public function evalReturnsExpressionResult() {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_formatExpression', 'getContext'));
		$sut->expects($this->once())->method('_formatExpression')->with('expression', 'return')
			->will($this->returnValue('$return_value = 30+12;'));
		$sut->expects($this->once())->method('getContext')->will($this->returnValue(new \ArrayObject()));

		$this->assertEquals(42, $sut->proxyCall('_eval', array('expression', 'return')));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _eval sets local context variables properly
	 * @covers \Lx\SIGen\InterfaceBuilder::_eval
	 */
	public function evalSetsLocalVariables() {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_formatExpression', 'getContext'));
		$sut->expects($this->once())->method('_formatExpression')->with('$key')
			->will($this->returnValue('$return_value = $key;'));
		$sut->expects($this->once())->method('getContext')
			->will($this->returnValue(new \ArrayObject(array('key' => 'value'))));

		$this->assertEquals('value', $sut->proxyCall('_eval', array('$key')));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	public function getArgumentDefinitionProvider() {
		return array(
			array('test', false, null, false, '$test'),
			array('test', true, 'value', false, '$test = \'value\''),
			array('test', true, "value\nwith\tspecialchars", false, "\$test = 'value\nwith\tspecialchars'"),
			array('test', true, 42, false, '$test = 42'), array('test', true, null, false, '$test = NULL'),
			array('test', true, false, false, '$test = false'), array(
				'test', true, array(1, 2, 3), false,
				"\$test = array (\n  0 => 1,\n  1 => 2,\n  2 => 3,\n)"
			), array('test', false, null, true, '&$test'),
		);
	}

	/**
	 * @test
	 * @testdox _getArgumentDefinition works correct
	 * @dataProvider getArgumentDefinitionProvider
	 * @covers \Lx\SIGen\InterfaceBuilder::_getArgumentDefinition
	 */
	public function getArgumentDefinition($name, $with_value, $value, $pass_by_reference, $result) {
		$this->assertEquals($result, $this->object->proxyCall('_getArgumentDefinition', array(
		                                                                                     $name, $with_value,
		                                                                                     $value,
		                                                                                     $pass_by_reference
		                                                                                )));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	public function getMockedParameterReflection($name, $has_default, $default_value, $passed_by_reference) {
		$param_reflection = $this->getMock('\ReflectionParam', array(
		                                                            'getName', 'isPassedByReference',
		                                                            'getDefaultValue', 'isDefaultValueAvailable'
		                                                       ), array(), '', false);
		$param_reflection->expects($this->any())->method('getName')->will($this->returnValue($name));
		$param_reflection->expects($this->any())->method('isDefaultValueAvailable')
			->will($this->returnValue($has_default));
		$param_reflection->expects($this->any())->method('getDefaultValue')
			->will($this->returnValue($default_value));
		$param_reflection->expects($this->any())->method('isPassedByReference')
			->will($this->returnValue($passed_by_reference));

		return $param_reflection;
	}

	public function getMethodArgumentsProvider() {
		return array(
			#     has_def def_value       ref     withdef par1    par2
			array(false, 'value', false, true, false, null),
			array(true, 'value', false, true, true, 'value'), array(true, 'value', true, true, false, null),
			array(true, null, true, true, false, null),

			array(false, 'value', false, false, false, null),
			array(true, 'value', false, false, false, null), array(true, 'value', true, false, false, null),
			array(true, null, true, false, false, null),
		);
	}

	/**
	 * @test
	 * @testdox _getMethodArguments properly calls _getArgumentDefinition
	 * @dataProvider getMethodArgumentsProvider
	 * @covers \Lx\SIGen\InterfaceBuilder::_getMethodArguments
	 */
	public function getMethodArguments($has_default, $default_value, $passed_by_reference, $with_defaults, $after_default_available, $after_default_value) {
		$mocked_param = $this->getMockedParameterReflection('param', $has_default, $default_value, $passed_by_reference);
		$method_reflection = $this->getMock('\ReflectionMethod', array('getParameters'), array(), '', false);
		$method_reflection->expects($this->once())->method('getParameters')
			->will($this->returnValue(array($mocked_param)));

		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_getArgumentDefinition'));
		$sut->expects($this->once())->method('_getArgumentDefinition')
			->with('param', $after_default_available, $after_default_value, $passed_by_reference);
		$sut->proxyCall('_getMethodArguments', array($method_reflection, $with_defaults));

	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _generateMethod works as expected
	 * @covers \Lx\SIGen\InterfaceBuilder::_generateMethod
	 */
	public function generateMethodWorksAsExpected() {
		$method_testname = 'test_method_name';
		$method_testdoc = '/** this is a comment */';
		$method_testargs = 'dummy argument list';

		$method_reflection = $this->getMock('\ReflectionMethod', array(), array(), '', false);
		$method_reflection->expects($this->once())->method('getName')
			->will($this->returnValue($method_testname));
		$method_reflection->expects($this->once())->method('getDocComment')
			->will($this->returnValue($method_testdoc));

		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_getMethodArguments'));
		$sut->expects($this->exactly(2))->method('_getMethodArguments')
			->with($method_reflection, $this->anything())->will($this->returnValue($method_testargs));

		$expected = sprintf(InterfaceBuilder::METHOD_FMT, $method_testdoc, $method_testname, $method_testargs, $method_testname, $method_testargs);
		$this->assertEquals($expected, $sut->proxyCall('_generateMethod', array($method_reflection)));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	public function getExposeExpressionProvider() {
		return array(
			array("/**\n* @expose ##test test##\n*/", false, 'test test'),
			array("/**\n* @expose      ##test##\n*/", false, 'test'),
			array("/**\n* @expose      \n*/", false, 'false'), array("/**\n* @expose \n*/", false, 'false'),
			array("/**\n* @expose\n*/", false, 'false'),
			array("/**\n*@expose ##test test##\n*/", false, 'test test'),
			array("/**\n*@expose ##test##\n*/", false, 'test'),
			array("/**\n*@expose  \n*/", false, 'false'), array("/**\n*@expose \n*/", false, 'false'),
			array("/**\n*@expose\n*/", false, 'false'), array("/**@expose ##test##*/", false, 'test'),
			array("/**@expose*/", false, 'false'), array("/**@expose\t##test\ttest##", false, "test\ttest"),
			array("some text without expose tag", false, 'false'),
			array("somt text without expose tag but defaults true", true, 'true'),
			array("/** @expose ##\$test## */", false, "\$test")
		);
	}

	/**
	 * @test
	 * @testdox getExpose properly recognizes @expose tag and an optional expression
	 * @dataProvider getExposeExpressionProvider
	 * @covers \Lx\SIGen\InterfaceBuilder::_getExposeExpression
	 */
	public function getExposeExpression($argument, $defaults_true, $expected) {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('getVariableIdentifier'));
		$sut->expects($this->atLeastOnce())->method('getVariableIdentifier')
			->will($this->returnValue('##%s##'));
		$this->assertSame($expected, $sut->proxyCall('_getExposeExpression', array($argument, $defaults_true)));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _meetsCondition calls _getExposeExpression and _eval
	 * @covers \Lx\SIGen\InterfaceBuilder::_meetsCondition
	 */
	public function meetsCondition() {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_eval', '_getExposeExpression'));
		$sut->expects($this->once())->method('_eval')->with('expression', true)->will($this->returnValue(true));
		$sut->expects($this->once())->method('_getExposeExpression')->with('doccomment', true)
			->will($this->returnValue('expression'));
		$this->assertTrue($sut->proxyCall('_meetsCondition', array('doccomment', true)));

	}

	// ---------------------------------------------------------------------------------------------------------------------

	public function replaceVarsProvider() {
		return array(
			array('##true##', 'true'), array('##false##', 'false'),
			array('##true## ##false##', 'true false'), array('##true## ##true##', 'true true'),
			array("##true##\n##false##", "true\nfalse"),
		);
	}

	/**
	 * @ŧest
	 *
	 * @testdox _replaceVars works as expected
	 * @dataProvider replaceVarsProvider
	 */
	public function replaceVars($test_code, $expected) {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_eval'));
		$sut->expects($this->any())->method('_eval')->with($this->anything(), true)
			->will($this->returnArgument(0));
		$this->assertEquals($expected, $sut->proxyCall('_replaceVars', array($test_code)));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _generateMethodBodies properly generates method bodies
	 * @covers \Lx\SIGen\InterfaceBuilder::_generateMethodBodies
	 */
	public function generateMethodBodies() {
		$dummy_body = 'document body';
		$dummmy_docblock = '/** docblock */';

		$method_reflection = $this->getMock('\ReflectionMethod', array(), array(), '', false);
		$method_reflection->expects($this->once())->method('getDocComment')
			->will($this->returnValue($dummmy_docblock));

		$class_reflection = $this->getMock('\ReflectionClass', array(), array(), '', false);
		$class_reflection->expects($this->once())->method('getMethods')->with(\ReflectionMethod::IS_PUBLIC)
			->will($this->returnValue(array($method_reflection)));

		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_meetsCondition', '_generateMethod'));
		$sut->expects($this->once())->method('_meetsCondition')->with($dummmy_docblock)
			->will($this->returnValue(true));
		$sut->expects($this->once())->method('_generateMethod')->with($method_reflection)
			->will($this->returnValue($dummy_body));

		$this->assertEquals(array($dummy_body), $sut->proxyCall('_generateMethodBodies', array($class_reflection)));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _generateClassBody returns false on not met condition in classes doccomment
	 * @covers \Lx\SIGen\InterfaceBuilder::_generateClassBody
	 */
	public function generateClassBodyReturnsFalsOnNoExpose() {
		$reflection = $this->getMock('\ReflectionClass', array('getDoccomment'), array(), '', false);

		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array('_meetsCondition'));
		$sut->expects($this->once())->method('_meetsCondition')->with($this->anything(), true)
			->will($this->returnValue(false));

		$this->assertFalse($sut->proxyCall('_generateClassBody', array(
		                                                              $reflection, 'interface_name',
		                                                              'parent_name'
		                                                         )));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _generateClassBody returns false if there are no methods to expose
	 * @covers \Lx\SIGen\InterfaceBuilder::_generateClassBody
	 */
	public function generateClassBodyReturnsFalseOnNoMethods() {
		$reflection = $this->getMock('\ReflectionClass', array('getDoccomment'), array(), '', false);

		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array(
		                                                           '_meetsCondition', '_generateMethodBodies'
		                                                      ));
		$sut->expects($this->once())->method('_meetsCondition')->with($this->anything(), true)
			->will($this->returnValue(true));
		$sut->expects($this->once())->method('_generateMethodBodies')->with($reflection)
			->will($this->returnValue(array()));

		$this->assertFalse($sut->proxyCall('_generateClassBody', array(
		                                                              $reflection, 'interface name',
		                                                              'parent_name'
		                                                         )));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox _generateClassBody returns correct class body
	 * @covers \Lx\SIGen\InterfaceBuilder::_generateClassBody
	 */
	public function generateClassBodyReturnsBody() {
		$reflection = $this->getMock('\ReflectionClass', array('getDoccomment'), array(), '', false);

		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilderProxy', array(
		                                                           '_meetsCondition', '_generateMethodBodies',
		                                                           'getProxyParentClass'
		                                                      ));
		$sut->expects($this->once())->method('_meetsCondition')->with($this->anything(), true)
			->will($this->returnValue(true));
		$sut->expects($this->once())->method('_generateMethodBodies')->with($reflection)
			->will($this->returnValue(array('method body')));

		$this->assertEquals("final class class_name extends parent_class_name {\nmethod body\n}\n", $sut->proxyCall('_generateClassBody', array(
		                                                                                                                                       $reflection,
		                                                                                                                                       'class_name',
		                                                                                                                                       'parent_class_name'
		                                                                                                                                  )));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	public function generateNamspaceNameProvider() {
		return array(
			array('', 'namespace_from_class'), array(null, 'namespace_from_class'),
			array('namespace_from_request', 'namespace_from_request'), array(42, 'namespace_from_class'),
			array(true, 'namespace_from_class'), array(false, 'namespace_from_class')
		);
	}

	/**
	 * @test
	 * @dataProvider generateNamspaceNameProvider
	 * @covers \Lx\SIGen\InterfaceBuilder::_generateNamespaceName
	 *
	 */
	public function generateNamespaceNameReturnsCorrectValue($namespace, $expected) {
		$class_reflection = $this->getMock('\ReflectionClass', array(), array(), '', false);
		$class_reflection->expects($this->any())->method('getNamespaceName')
			->will($this->returnValue('namespace_from_class'));

		$this->object->proxyCall('_generateNamespaceName', array($class_reflection, $namespace));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function generateNamespaceBody() {
		$this->assertEquals("namespace namespace_name {\nclass_body\n}\n", $this->object->proxyCall('_generateNamespaceBody', array(
		                                                                                                                           'class_body',
		                                                                                                                           'namespace_name'
		                                                                                                                      )));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	public function generateClassNameProvider() {
		return array(
			array(false, 'MockClassProxy', 1), array(true, 'MockClassProxy', 1),
			array('classname', 'classname', 0), array(42, 'MockClassProxy', 1),
		);
	}

	/**
	 * @test
	 * @testdox generateClassName returns proper classnames
	 * @dataProvider generateClassNameProvider
	 * @covers \Lx\SIGen\InterfaceBuilder::_generateClassName
	 */
	public function generateClassName($classname, $result, $ref_calls) {
		$reflection = $this->getMock('\ReflectionClass', array('getShortName'), array(), '', false);
		$reflection->expects($this->exactly($ref_calls))->method('getShortName')
			->will($this->returnValue('MockClass'));

		$this->assertEquals($result, $this->object->proxyCall('_generateClassName', array(
		                                                                                 $reflection, $classname
		                                                                            )));
	}

	// --------------------------------------------------------------------------------------------------------------------- ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox generateInterfaceClass throws exception when argument is not an object
	 * @expectedException \Lx\SIGen\Exception\InvalidArgumentException
	 * @covers \Lx\SIGen\InterfaceBuilder::generateInterfaceClassSource
	 */
	public function generateInterfaceClassSourceWithNonObject() {
		$this->object->generateInterfaceClassSource('not an object');
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox generateInterfaceClass throws an exception when no proxy parent class is set
	 * @expectedException \Lx\SIGen\Exception\RequirementException
	 * @covers \Lx\SIGen\InterfaceBuilder::generateInterfaceClassSource
	 */
	public function generateInterfaceClassSourceWithoutProxyParent() {
		$backup = $this->object->proxyGetStatic('_default_proxy_parent_class');

		$this->object->proxySet('_proxy_parent_class', null);
		$this->object->proxySetStatic('_default_proxy_parent_class', null);
		$this->object->generateInterfaceClassSource(new \stdClass());

		$this->object->proxySet('_proxy_parent_class', $backup);
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox generateInterfaceClassSource returns false on empty class body
	 * @covers \Lx\SIGen\InterfaceBuilder::generateInterfaceClassSource
	 */
	public function generateInterfaceClassSourceReturnsFalseOnNoClassBody() {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilder', array(
		                                                      'getProxyParentClass', '_generateClassName',
		                                                      '_generateNamespaceName', '_generateClassBody'
		                                                 ));

		$sut->expects($this->once())->method('getProxyParentClass')
			->will($this->returnValue('proxy_parent_class'));
		$sut->expects($this->once())->method('_generateClassName')->will($this->returnValue('class_name'));
		$sut->expects($this->once())->method('_generateNamespaceName')
			->will($this->returnValue('namespace_name'));
		$sut->expects($this->once())->method('_generateClassBody')->will($this->returnValue(false));

		$this->assertFalse($sut->generateInterfaceClassSource(new \stdClass()));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox generateInterfaceClassSource returns fully qualified class name and correct source code
	 * @covers \Lx\SIGen\InterfaceBuilder::generateInterfaceClassSource
	 */
	public function generateInterfaceClassSourceReturnsNameAndSource() {
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilder', array(
		                                                      'getProxyParentClass', '_generateClassName',
		                                                      '_generateNamespaceName', '_generateClassBody',
		                                                      '_replaceVars'
		                                                 ));
		$expected_code = "namespace namespace_name {\nclass_body\n}\n";
		$sut->expects($this->once())->method('getProxyParentClass')
			->will($this->returnValue('proxy_parent_class'));
		$sut->expects($this->once())->method('_generateClassName')->with($this->anything(), $this->anything())
			->will($this->returnArgument(1));
		$sut->expects($this->once())->method('_generateNamespaceName')
			->with($this->anything(), 'namespace_name')->will($this->returnArgument(1));
		$sut->expects($this->once())->method('_generateClassBody')
			->with($this->anything(), 'class_name', 'proxy_parent_class')
			->will($this->returnValue('class_body'));
		$sut->expects($this->once())->method('_replaceVars')->with($expected_code)
			->will($this->returnArgument(0));

		$this->assertEquals(array(
		                         "namespace_name\\class_name", $expected_code
		                    ), $sut->generateInterfaceClassSource(new \stdClass(), 'class_name', 'namespace_name'));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox generateInterface evals source and returns fully qualified class name
	 * @covers \Lx\SIGen\InterfaceBuilder::generateInterfaceClass
	 */
	public function generateInterfaceReturnsClassNameAndSource() {
		$interface_object = new \stdClass();
		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilder', array('generateInterfaceClassSource', '_eval'));
		$sut->expects($this->once())->method('generateInterfaceClassSource')
			->with($interface_object, 'class_name', 'namespace_name')
			->will($this->returnValue(array('namespace_name\\class_name', 'true;')));
		$this->assertEquals("namespace_name\\class_name", $sut->generateInterfaceClass($interface_object, 'class_name', 'namespace_name'));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox generateInstance returns generated class instance
	 * @covers \Lx\SIGen\InterfaceBuilder::generateInstance
	 */
	public function generateInstanceReturnsGeneratedClassInstance() {
		$service_template_object = new \stdClass();

		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilder', array('generateInterfaceClass'));
		$sut->expects($this->once())->method('generateInterfaceClass')->with($service_template_object)
			->will($this->returnValue('\stdClass'));

		$this->assertInstanceOf('\stdClass', $sut->generateInstance($service_template_object));
	}

	// ---------------------------------------------------------------------------------------------------------------------

	/**
	 * @test
	 * @testdox generateInstance returns false when no class has been generated
	 * @covers \Lx\SIGen\InterfaceBuilder::generateInstance
	 */
	public function generateInstanceReturnsFalseOnNoClassGenerated() {
		$service_template_object = new \stdClass();

		$sut = $this->getMock('\Lx\SIGen\InterfaceBuilder', array('generateInterfaceClass'));
		$sut->expects($this->once())->method('generateInterfaceClass')->with($service_template_object)
			->will($this->returnValue(false));

		$this->assertFalse($sut->generateInstance($service_template_object));
	}
}
