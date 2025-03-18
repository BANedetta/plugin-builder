<?php

$srcPath = __DIR__ . "/src";
$phar = new Phar(__DIR__ . "/plugin.phar", 0, "plugin.phar");

$phar->buildFromDirectory(__DIR__, '/^(?!.*(builder|scripts|build\.php)).*$/');

$autoloadCode = <<<'PHP'
<?php

foreach (glob(__DIR__ . "/virions/*.phar") as $pharFile) {
	include_once "phar://" . $pharFile . "/autoload.php";
}

$vendorAutoload = __DIR__ . "/vendor/autoload.php";

if (file_exists($vendorAutoload)) {
	require_once $vendorAutoload;
}
PHP;

$phar->addFromString("autoload.php", $autoloadCode);

$phar->setStub("<?php
	Phar::mapPhar('plugin.phar');
	require 'phar://plugin.phar/autoload.php';
	__HALT_COMPILER();
?>");