<?php

spl_autoload_register(function (string $class): void {
	$path = __DIR__ . "/" . str_replace("\\", "/", $class) . ".php";

	if (file_exists($path)) {
		require_once $path;
	}
});
