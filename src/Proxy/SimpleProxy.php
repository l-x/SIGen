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

/**
 * Simple proxy class
 */
abstract class SimpleProxy {

	/**
	 * Keeps the service object
	 *
	 * @var null|object
	 */
	protected $_service_template = null;

	/**
	 * Class constructor
	 *
	 * @param $service_template
	 */
	public function __construct($service_template) {
		if(!is_object($service_template)) {
			throw new SIGen\Exception\InvalidArgumentException('Argument must be an object');
		}
		$this->_service_template = $service_template;
	}

	/**
	 * Method stub for processing method invokation arguments.
	 *
	 * This method is intended for overwriting in classes extending this class.
	 *
	 * @param string $method
	 * @param array $arguments
	 *
	 * @return array
	 */
	protected function preProcess($method, $arguments = array()) {
		return array($method, $arguments);
	}

	/**
	 * Method stub for processing method results.
	 *
	 * This method is intended for overwriting in classes extending this class
	 *
	 * @param string $method
	 * @param mixed|null $result
	 *
	 * @return mixed|null
	 */
	protected function postProcess($method, $result = null) {
		return $result;
	}

	/**
	 * Calls a method in service object
	 *
	 * @param string $method
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	protected function call($method, $arguments = array()) {
		list($processed_method, $processed_arguments) = $this->preProcess($method, $arguments);
		$result = call_user_func_array(array($this->_service_template, $processed_method), $processed_arguments);
		$processed_result = $this->postProcess($method, $result);
		return $processed_result;
	}
}
