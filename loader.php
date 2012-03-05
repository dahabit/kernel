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
	'Input'            => 'Fuel\\Kernel\\Input',
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
	->set_namespace(false)
	->add_classes(array(
		'Fuel\\Kernel\\Application\\Base' => __DIR__.'/classes/Application/Base.php',
		'Fuel\\Kernel\\Controller\\Base' => __DIR__.'/classes/Controller/Base.php',
		'Fuel\\Kernel\\Cookie\\Base' => __DIR__.'/classes/Cookie/Base.php',
		'Fuel\\Kernel\\Data\\Base' => __DIR__.'/classes/Data/Base.php',
		'Fuel\\Kernel\\Data\\Config' => __DIR__.'/classes/Data/Config.php',
		'Fuel\\Kernel\\Data\\Language' => __DIR__.'/classes/Data/Language.php',
		'Fuel\\Kernel\\DiC\\Base' => __DIR__.'/classes/DiC/Base.php',
		'Fuel\\Kernel\\DiC\\Dependable' => __DIR__.'/classes/DiC/Dependable.php',
		'Fuel\\Kernel\\Loader\\Composer' => __DIR__.'/classes/Loader/Composer.php',
		'Fuel\\Kernel\\Loader\\Loadable' => __DIR__.'/classes/Loader/Loadable.php',
		'Fuel\\Kernel\\Loader\\Package' => __DIR__.'/classes/Loader/Package.php',
		'Fuel\\Kernel\\Notifier\\Base' => __DIR__.'/classes/Notifier/Base.php',
		'Fuel\\Kernel\\Notifier\\Notifiable' => __DIR__.'/classes/Notifier/Notifiable.php',
		'Fuel\\Kernel\\Parser\\Parsable' => __DIR__.'/classes/Parser/Parsable.php',
		'Fuel\\Kernel\\Parser\\Php' => __DIR__.'/classes/Parser/Php.php',
		'Fuel\\Kernel\\Request\\Exception_404' => __DIR__.'/classes/Request/Exception/404.php',
		'Fuel\\Kernel\\Request\\Exception_500' => __DIR__.'/classes/Request/Exception/404.php',
		'Fuel\\Kernel\\Request\\Base' => __DIR__.'/classes/Request/Base.php',
		'Fuel\\Kernel\\Request\\Exception' => __DIR__.'/classes/Request/Exception.php',
		'Fuel\\Kernel\\Request\\Fuel' => __DIR__.'/classes/Request/Fuel.php',
		'Fuel\\Kernel\\Response\\Base' => __DIR__.'/classes/Response/Base.php',
		'Fuel\\Kernel\\Response\\Responsible' => __DIR__.'/classes/Response/Responsible.php',
		'Fuel\\Kernel\\Route\\Base' => __DIR__.'/classes/Route/Base.php',
		'Fuel\\Kernel\\Route\\Fuel' => __DIR__.'/classes/Route/Fuel.php',
		'Fuel\\Kernel\\Route\\Task' => __DIR__.'/classes/Route/Task.php',
		'Fuel\\Kernel\\Security\\Crypt\\Cryptable' => __DIR__.'/classes/Security/Crypt/Cryptable.php',
		'Fuel\\Kernel\\Security\\Csrf\\Base' => __DIR__.'/classes/Security/Csrf/Base.php',
		'Fuel\\Kernel\\Security\\Csrf\\Cookie' => __DIR__.'/classes/Security/Csrf/Cookie.php',
		'Fuel\\Kernel\\Security\\String\\Base' => __DIR__.'/classes/Security/String/Base.php',
		'Fuel\\Kernel\\Security\\String\\Htmlentities' => __DIR__.'/classes/Security/String/Htmlentities.php',
		'Fuel\\Kernel\\Security\\Base' => __DIR__.'/classes/Security/Base.php',
		'Fuel\\Kernel\\Task\\Base' => __DIR__.'/classes/Task/Base.php',
		'Fuel\\Kernel\\View\\Base' => __DIR__.'/classes/View/Base.php',
		'Fuel\\Kernel\\View\\Viewable' => __DIR__.'/classes/View/Viewable.php',
		'Fuel\\Kernel\\Cli' => __DIR__.'/classes/Cli.php',
		'Fuel\\Kernel\\Environment' => __DIR__.'/classes/Environment.php',
		'Fuel\\Kernel\\Error' => __DIR__.'/classes/Error.php',
		'Fuel\\Kernel\\Input' => __DIR__.'/classes/Input.php',
		'Fuel\\Kernel\\Loader' => __DIR__.'/classes/Loader.php',
		'Fuel\\Kernel\\Log' => __DIR__.'/classes/Log.php',
	))
	->add_class_aliases(array(
		'Classes\\Application\\Base' => 'Fuel\\Kernel\\Application\\Base',
		'Classes\\Controller\\Base' => 'Fuel\\Kernel\\Controller\\Base',
		'Classes\\Data\\Base' => 'Fuel\\Kernel\\Data\\Base',
		'Classes\\Data\\Config' => 'Fuel\\Kernel\\Data\\Config',
		'Classes\\Data\\Language' => 'Fuel\\Kernel\\Data\\Language',
		'Classes\\DiC\\Base' => 'Fuel\\Kernel\\DiC\\Base',
		'Classes\\Loader\\Base' => 'Fuel\\Kernel\\Loader\\Base',
		'Classes\\Request\\Base' => 'Fuel\\Kernel\\Request\\Base',
		'Classes\\Request\\Fuel' => 'Fuel\\Kernel\\Request\\Fuel',
		'Classes\\Response\\Base' => 'Fuel\\Kernel\\Response\\Base',
		'Classes\\Route\\Base' => 'Fuel\\Kernel\\Route\\Base',
		'Classes\\Route\\Fuel' => 'Fuel\\Kernel\\Route\\Fuel',
		'Classes\\Route\\Task' => 'Fuel\\Kernel\\Route\\Task',
		'Classes\\Task\\Base' => 'Fuel\\Kernel\\Task\\Base',
		'Classes\\View\\Base' => 'Fuel\\Kernel\\View\\Base',
	));
