<?php

/**
 * @package    SIGen
 * @author     Alexander Wühr <lx@boolshit.de>
 * @copyright  2013 Alexander Wühr <lx@boolshit.de>
 * @license    http://opensource.org/licenses/MIT  The MIT License (MIT)
 * @link       https://boolshit.de
 */

namespace Lx\SIGen;

class InterfaceBuilder {

	/**
	 * printf template for method body generation
	 *
	 * @var string
	 */
	const METHOD_FMT = "%s\npublic function %s(%s) { return parent::call('%s', array(%s)); }\n";

	/**
	 * printf template for class body generation
	 *
	 * @var string
	 */
	const CLASS_FMT = "final class %s extends %s {\n%s\n}\n";

	/**
	 * printf template for namespace body generation
	 *
	 * @var string
	 */
	const NAMESPACE_FMT = "namespace %s {\n%s\n}\n";

	/**
	 * Stores the context data
	 *
	 * @var \ArrayObject|null
	 */
	protected $_context = null;


	/**
	 * Name of the docblock tag which controls if to expose a method or the entire class
	 *
	 * @var string
	 */
	protected static $_expose_tag_name = 'expose';

	/**
	 * Format string for variable recognition
	 *
	 * @var string
	 */
	protected static $_variable_format = "##%s##";

	/**
	 * Default proxy class to use for class generation
	 *
	 * @var string
	 */
	protected $_proxy_parent_class = null;

	/**
	 * Default proxy parent class, which is used when $_proxy_parent_class is null
	 *
	 * @var string
	 */
	protected static $_default_proxy_parent_class = '\SIGen\Proxy\SimpleProxy';


	/**
	 * Class constructor
	 *
	 * @param array|\ArrayObject $context
	 */
	public function __construct($context = array()) {
		$this->setContext($context);
	}

	/**
	 * Sets the static default proxy parent class
	 *
	 * @param string $classname
	 */
	public static function setDefaultProxyParentClass($classname) {
		static::_validateParentProxyClass($classname);
		static::$_default_proxy_parent_class = $classname;
	}

	/**
	 * Returns the static default proxy parent class
	 *
	 * @return string
	 */
	public static function getDefaultProxyParentClass() {
		return static::$_default_proxy_parent_class;
	}

	/**
	 * Assures that the $classname is an existing class and implements the abstract class SimpleProxy
	 *
	 * @param $classname
	 *
	 * @return bool
	 * @throws Exception\InvalidArgumentException
	 */
	protected static function _validateParentProxyClass($classname) {
		if(!class_exists($classname)) {
			throw new Exception\InvalidArgumentException("Class '$classname' could not be found");
		}

		$cf = new \ReflectionClass($classname);
		if(!$cf->hasMethod('call')) {
			throw new Exception\InvalidArgumentException("Class '$classname' does not have the required method 'call'");
		}

		return true;
	}

	/**
	 * Returns the class to use as parent when generating proxy class
	 *
	 * @return null|string
	 */
	public function getProxyParentClass() {
		if(!$classname = $this->_proxy_parent_class) {
			$classname = $this->getDefaultProxyParentClass();
		}

		return $classname;
	}

	/**
	 * Sets the class to use as parent when generating proxy class
	 *
	 * @param string $classname
	 *
	 * @return \Lx\SIGen\InterfaceBuilder
	 * @throws Exception\InvalidArgumentException
	 */
	public function setProxyParentClass($classname) {
		if($classname !== null) {
			$this->_validateParentProxyClass($classname);
		}
		$this->_proxy_parent_class = $classname;

		return $this;
	}

	/**
	 * Sets the context data
	 *
	 * @param array|\ArrayObject $context
	 *
	 * @return \Lx\SIGen\InterfaceBuilder
	 * @throws \Lx\SIGen\Exception\InvalidArgumentException
	 */
	public function setContext($context) {
		if(is_array($context)) {
			$context = new \ArrayObject($context);
		}

		if(!$context instanceof \ArrayObject) {
			throw new Exception\InvalidArgumentException('Argument must be array or instance of ArrayObject');
		}

		$this->_context = $context;

		return $this;
	}

	/**
	 * Returns the current context
	 *
	 * @return \ArrayObject|null
	 */
	public function getContext() {
		return $this->_context;
	}

	/**
	 * Formats the given expression for use in _eval
	 *
	 * @param string $expression
	 * @param bool $return
	 *
	 * @return string
	 */
	protected function _formatExpression($expression, $return) {
		$return_stmt = '%s;';
		if($return) {
			$return_stmt = '$return_value = '.$return_stmt;
		}

		return sprintf($return_stmt, $expression);
	}

	/**
	 * Returns the static member for variable identification in service classes
	 *
	 * @return string
	 */
	public function getVariableIdentifier() {
		return static::$_variable_format;
	}


	/**
	 * Executes the expression and optionally returns the result
	 *
	 * @param string $expression
	 * @param bool $return
	 *
	 * @return mixed
	 * @throws \Lx\SIGen\Exception\EvalException
	 *
	 * @todo Evaluate better methods for executing php code and fetching errors
	 */
	protected function _eval($expression, $return = false) {
		$return_value = true;
		foreach($this->getContext() as $context_var => $context_val) {
			$$context_var = $context_val;
		}
		$expression = $this->_formatExpression($expression, $return);
		set_error_handler(function ($error_nr, $error_msg) {
			throw new Exception\EvalException($error_msg, $error_nr);
		}, E_ALL & ~E_DEPRECATED);

		$eval_result = @eval($expression);
		restore_error_handler();

		if($eval_result === false) {
			throw new Exception\EvalException(sprintf("Parse error in expression \"%s\"", $expression));
		}

		return $return_value;
	}

	/**
	 * Generates an argument definition for a single method argument
	 *
	 * @param string $name
	 * @param bool $with_value
	 * @param mixed $value
	 * @param bool $pass_by_reference
	 *
	 * @return string
	 */
	protected function _getArgumentDefinition($name, $with_value = false, $value = null, $pass_by_reference = false) {
		$repr = '';
		if($pass_by_reference) {
			$repr = '&';
		}
		$repr .= '$'.$name;
		if($with_value) {
			$repr .= ' = '.var_export($value, true);
		}

		return $repr;
	}

	/**
	 * Generates argument string from method reflection for use in method signatures and proxy calls
	 *
	 * @param \ReflectionMethod $method_reflection
	 * @param bool $with_defaults
	 *
	 * @return string
	 */
	protected function _getMethodArguments(\ReflectionMethod $method_reflection, $with_defaults) {
		$argument_definitions = array();
		foreach($method_reflection->getParameters() as $parameter_reflection) {
			$name = $parameter_reflection->getName();
			$passed_by_reference = $parameter_reflection->isPassedByReference();
			$default_available = $parameter_reflection->isDefaultValueAvailable() && $with_defaults && !$passed_by_reference;
			$default_value = null;
			if($default_available) {
				$default_value = $parameter_reflection->getDefaultValue();
			}
			$argument_definitions[] = $this->_getArgumentDefinition($name, $default_available, $default_value, $passed_by_reference);
		}

		return join(', ', $argument_definitions);
	}

	/**
	 * Generates body for proxy method
	 *
	 * @param \ReflectionMethod $method_reflection
	 *
	 * @return string
	 */
	protected function _generateMethod(\ReflectionMethod $method_reflection) {
		$signature_arguments = $this->_getMethodArguments($method_reflection, true);
		$forward_arguments = $this->_getMethodArguments($method_reflection, false);
		$method_name = $method_reflection->getName();
		$method_docblock = $method_reflection->getDocComment();

		$method_signature = sprintf(static::METHOD_FMT, $method_docblock, $method_name, $signature_arguments, $method_name, $forward_arguments);

		return $method_signature;
	}

	/**
	 * Returns the expose expression of a given doc comment
	 *
	 * @param string $doccomment
	 * @param bool $defaults_true
	 *
	 * @return string|boolean
	 */
	protected function _getExposeExpression($doccomment, $defaults_true = false) {
		$match = array();
		$expression = var_export($defaults_true, true);

		$variable_format = $this->getVariableIdentifier();

		$variable_pattern = sprintf($variable_format, '(.+?)');
		$expose_tag = static::$_expose_tag_name;

		$expose_pattern = "/@$expose_tag\W*$variable_pattern/i";

		preg_match($expose_pattern, $doccomment, $match);
		if(count($match) > 1 && $match[1]) {
			$expression = $match[1];
		}

		return $expression;
	}

	/**
	 * Returns wether to expose or not the corresponding class or method
	 *
	 * @param string $doccomment
	 */
	protected function _meetsCondition($doccomment, $defaults_true = false) {
		return $this->_eval($this->_getExposeExpression($doccomment, $defaults_true), true);
	}

	/**
	 * Replaces all variables in given source
	 *
	 * @param string $source
	 *
	 * @return string
	 */
	protected function _replaceVars($source) {
		$matches = array();
		$variable_format = $this->getVariableIdentifier();
		$variable_pattern = '/'.sprintf($variable_format, '(.+?)').'/i';
		preg_match_all($variable_pattern, $source, $matches);
		if(count($matches) == 2) {
			for($idx = 0; $idx < count($matches[0]); $idx++) {
				$source = str_replace($matches[0][$idx], $this->_eval($matches[1][$idx], true), $source);
			}
		}

		return $source;
	}

	/**
	 * Generates array of method bodies for use as class body
	 *
	 * @param \ReflectionClass $class_reflection
	 *
	 * @return array
	 */
	protected function _generateMethodBodies(\ReflectionClass $class_reflection) {
		$method_bodies = array();
		foreach($class_reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method_reflection) {
			if($this->_meetsCondition($method_reflection->getDocComment())) {
				$method_bodies[] = $this->_generateMethod($method_reflection);
			}
		}

		return $method_bodies;
	}

	/**
	 * Generates the namespace name for the interface class
	 *
	 * @param \ReflectionClass $class_reflection
	 * @param bool $namespace
	 *
	 * @return string
	 */
	protected function _generateNamespaceName(\ReflectionClass $class_reflection, $namespace = false) {
		if(is_null($namespace) || $namespace === '') {
			return '';
		}

		if($namespace === false) {
			return $class_reflection->getNamespaceName();
		}

		if(is_string($namespace)) {
			return $namespace;
		}

		return $class_reflection->getNamespaceName();
	}

	/**
	 * Returns the namespace body including the class and method definitions
	 *
	 * @param \ReflectionClass $class_reflection
	 * @param bool|string $namespace
	 *
	 * @return bool|string
	 */
	protected function _generateNamespaceBody($class_body, $namespace) {
		$namespace_body = sprintf(static::NAMESPACE_FMT, $namespace, $class_body);

		return $namespace_body;
	}

	/**
	 * Generates classname for proxy class
	 *
	 * @param \ReflectionClass $class_reflection
	 * @param bool|string $classname
	 *
	 * @return string
	 */
	protected function _generateClassName(\ReflectionClass $class_reflection, $classname = false) {
		if(!is_string($classname)) {
			$classname = $class_reflection->getShortName().'Proxy';
		}

		return $classname;
	}

	/**
	 * Generates class body with method bodies
	 *
	 * @param \ReflectionClass $class_reflection
	 * @param bool|string $classname
	 *
	 * @return bool|string
	 */
	protected function _generateClassBody(\ReflectionClass $class_reflection, $interface_class_name, $proxy_parent_class) {
		if(!$this->_meetsCondition($class_reflection->getDocComment(), true)) {
			return false;
		}

		if(!$method_bodies = $this->_generateMethodBodies($class_reflection)) {
			return false;
		}

		$class_body = sprintf(static::CLASS_FMT, $interface_class_name, $proxy_parent_class, join("\n", $method_bodies));

		return $class_body;
	}

	/**
	 * Generates interface class sourcecode, returns fully qualified classname and source
	 *
	 * @param object $service_tpl_object
	 *
	 * @throws Exception\RequirementException
	 * @throws Exception\InvalidArgumentException
	 */
	public function generateInterfaceClassSource($service_tpl_object, $interface_class_name = false, $namespace_name = false) {
		if(!is_object($service_tpl_object)) {
			throw new Exception\InvalidArgumentException('Argument is not an object: '.print_r($service_tpl_object, true));
		}

		if(!$proxy_parent_class = $this->getProxyParentClass()) {
			throw new Exception\RequirementException('A proxy client class is required for class generation');
		}

		$class_reflection = new \ReflectionClass($service_tpl_object);

		$interface_class_name = $this->_generateClassName($class_reflection, $interface_class_name);
		$namespace_name = $this->_generateNamespaceName($class_reflection, $namespace_name);

		if(!$class_body = $this->_generateClassBody($class_reflection, $interface_class_name, $proxy_parent_class)) {
			return false;
		}
		;

		$namespace_body = $this->_replaceVars(sprintf(static::NAMESPACE_FMT, $namespace_name, $class_body));

		return array("$namespace_name\\$interface_class_name", $namespace_body);
	}

	/**
	 * Generates interface class and returns fully qualified classname
	 *
	 * @param object $service_tpl_object
	 * @param bool $interface_class_name
	 * @param bool $namespace_name
	 *
	 * @return mixed
	 */
	public function generateInterfaceClass($service_tpl_object, $interface_class_name = false, $namespace_name = false) {
		list($interface_class_name, $interface_class_source) = $this->generateInterfaceClassSource($service_tpl_object, $interface_class_name, $namespace_name);
		eval($interface_class_source);

		return $interface_class_name;
	}

	/**
	 * Generates the interface class and returns an instance of this class
	 *
	 * @param object $service_tpl_object
	 * @param bool|string $interface_class_name
	 * @param bool|string $namespace_name
	 *
	 * @return bool|object
	 */
	public function generateInstance($service_tpl_object, $interface_class_name = false, $namespace_name = false) {
		if(!$class_name = $this->generateInterfaceClass($service_tpl_object, $interface_class_name, $namespace_name)) {
			return false;
		}
		$class_instance = new $class_name($service_tpl_object);

		return $class_instance;
	}
}
