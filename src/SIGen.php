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
			$path_fragments = explode('\\', $class_name);
			$path_fragments[0] = __DIR__; // overwrite vendor namespace with __DIR__
			$class_file = join(DIRECTORY_SEPARATOR, $path_fragments).$extension;
			if(is_file($class_file)) {
				include_once($class_file);
				break;
			}
		}
	});
