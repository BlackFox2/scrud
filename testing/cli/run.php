<?php
if (php_sapi_name() <> 'cli') die('Console usage only');

require_once __DIR__ . '/../../vendor/autoload.php';

require_once 'config.php';

foreach ($Connections as $connection_name => $Connection) {

	echo "[ {$connection_name} ]\r\n";

	\BlackFox2\Instance::addLinks(['BlackFox2\Database' => $Connection]);

	foreach ($Tests as $Test) {
		/** @var BlackFox2\Test $Test */
		$Test = new $Test;
		$Test->RunAllForClient();
	}

}