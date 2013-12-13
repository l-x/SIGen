<?php

/**
 * @package    SIGen
 * @author     Alexander Wühr <lx@boolshit.de>
 * @copyright  2013 Alexander Wühr <lx@boolshit.de>
 * @license    http://opensource.org/licenses/MIT  The MIT License (MIT)
 * @link       https://boolshit.de
 */

namespace Lx\SIGen;

spl_autoload_extensions('.php');
spl_autoload_register();
spl_autoload_register(function ($class_name) {
		foreach(explode(',', spl_autoload_extensions()) as $extension) {
			$class_file = __DIR__.str_replace('\\', DIRECTORY_SEPARATOR, str_replace(__NAMESPACE__, '', $class_name)).$extension;
			if(is_file($class_file)) {
				include_once($class_file);
				break;
			}
		}
	});
