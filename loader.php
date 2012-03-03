<?php
/**
 * Generate Package Loader object and related configuration
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

// Add the Kernel path to the globally available paths
$env->add_path('kernel', __DIR__, true);

// Add some Kernel classes to the global DiC
$env->dic->set_classes(array(
	'Cli'              => 'Fuel\\Kernel\\Cli',
	'Config'           => 'Fuel\\Kernel\\Data\\Config',
	'Cookie'           => 'Fuel\\Kernel\\Cookie\\Base',
	'Error'            => 'Fuel\\Kernel\\Error',
	'Language'         => 'Fuel\\Kernel\\Data\\Language',
	'Loader:Composer'  => 'Fuel\\Kernel\\Loader\\Composer',
	'Loader:Package'   => 'Fuel\\Kernel\\Loader\\Package',
	'Log'              => 'Fuel\\Kernel\\Log',
	'Notifier'         => 'Fuel\\Kernel\\Notifier\\Base',
	'Parser'           => 'Fuel\\Kernel\\Parser\\Php',
	'Request'          => 'Fuel\\Kernel\\Request\\Fuel',
	'Response'         => 'Fuel\\Kernel\\Response\\Base',
	'Route'            => 'Fuel\\Kernel\\Route\\Fuel',
	'Route:Task'       => 'Fuel\\Kernel\\Route\\Task',
	'Security'         => 'Fuel\\Kernel\\Security\\Base',
	'Security_Csrf'    => 'Fuel\\Kernel\\Security\\Csrf\\Cookie',
	'Security_String'  => 'Fuel\\Kernel\\Security\\String\\Htmlentities',
	'View'             => 'Fuel\\Kernel\\View\\Base',
));

// Forge & return the Kernel Package object
return $env->forge('Loader:Package')
	->set_path(__DIR__)
	->set_namespace('Fuel\\Kernel')
	->add_class_aliases(array(
		'Classes\\Application\\Base'  => 'Fuel\\Kernel\\Application\\Base',

		'Classes\\Controller\\Base'  => 'Fuel\\Kernel\\Controller\\Base',

		'Classes\\Data\\Base'      => 'Fuel\\Kernel\\Data\\Base',
		'Classes\\Data\\Config'    => 'Fuel\\Kernel\\Data\\Config',
		'Classes\\Data\\Language'  => 'Fuel\\Kernel\\Data\\Language',

		'Classes\\DiC\\Base'  => 'Fuel\\Kernel\\DiC\\Base',

		'Classes\\Loader\\Base'  => 'Fuel\\Kernel\\Loader\\Base',

		'Classes\\Presenter\\Base'  => 'Fuel\\Kernel\\Presenter\\Base',

		'Classes\\Request\\Base'  => 'Fuel\\Kernel\\Request\\Base',
		'Classes\\Request\\Fuel'  => 'Fuel\\Kernel\\Request\\Fuel',

		'Classes\\Response\\Base'  => 'Fuel\\Kernel\\Response\\Base',

		'Classes\\Route\\Base'  => 'Fuel\\Kernel\\Route\\Base',
		'Classes\\Route\\Fuel'  => 'Fuel\\Kernel\\Route\\Fuel',
		'Classes\\Route\\Task'  => 'Fuel\\Kernel\\Route\\Task',

		'Classes\\Task\\Base'   => 'Fuel\\Kernel\\Task\\Base',

		'Classes\\View\\Base'  => 'Fuel\\Kernel\\View\\Base',
	));
