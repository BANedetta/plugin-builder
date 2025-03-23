<?php

$stub = <<<'STUB'
<?php

Phar::mapPhar("plugin.phar");
require_once "phar://plugin.phar/autoload.php";
__HALT_COMPILER();
STUB;

$phar = new Phar(__DIR__ . "/plugin.phar", 0, "plugin.phar");
$phar->startBuffering();

$directory = new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($directory);
$filtered = new CallbackFilterIterator($iterator, function ($file) {
	$pathParts = explode(DIRECTORY_SEPARATOR, $file->getRealPath());

	foreach ($pathParts as $part) {
		if (strpos($part, ".") === 0) {
			return false;
		}
	}
	return true;
});

$phar->buildFromIterator($filtered, __DIR__);
$phar->setStub($stub);

$phar->compressFiles(Phar::GZ);

$phar->stopBuffering();
