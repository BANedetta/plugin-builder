<?php

$virions = __DIR__ . "/virions/autoload.php";
$vendor = __DIR__ . "/vendor/autoload.php";

if (file_exists($virions)) {
	require_once $virions;
}

if (file_exists($vendor)) {
	require_once $vendor;
}
