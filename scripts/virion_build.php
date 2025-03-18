<?php

$srcPath = __DIR__ . "/src";

$phar = new Phar(__DIR__ . "/virion.phar", 0, "virion.phar");

$phar->buildFromDirectory(__DIR__, '/^(?!.*(build\.php)).*$/');

$autoloadCode = <<<'PHP'
<?php

spl_autoload_register(function ($class) {
	$classPath = str_replace("\\", "/", $class) . ".php";
	$file = "phar://virion.phar/" . $classPath;

	if (file_exists($file)) {
		require_once $file;
	}
});
PHP;

$phar->addFromString("autoload.php", $autoloadCode);

$phar->setStub("<?php
	Phar::mapPhar('virion.phar');
	require 'phar://virion.phar/autoload.php';
	__HALT_COMPILER();
?>");