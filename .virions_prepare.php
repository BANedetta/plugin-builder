<?php

$path = __DIR__ . "/virions";

if (is_dir($path)) {
	remove($path);
}

if (!mkdir($path, 0777, true) && !is_dir($path)) {
	die("\n❌ Ошибка: Не удалось создать директорию '$path'. Проверьте права доступа.");
}

function downloadVirions(): void
{
	global $path;

	$virionsFile = __DIR__ . "/virions.txt";

	if (!file_exists($virionsFile) || !($virions = file($virionsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))) {
		die("\n❌ Ошибка: Файл 'virions.txt' пуст или отсутствует. Проверьте наличие файла с ресурсами.");
	}

	echo "\n📥 Начало скачивания вирионов...";

	foreach ($virions as $virion) {
		[$id, $name] = array_map("trim", explode(";", $virion));
		$savePath = "$path/$name.phar";
		$url = "https://poggit.pmmp.io/r/$id";

		echo "\n📥 Скачиваю вирион '$name' (ID: $id) с $url...";

		if (!($data = @file_get_contents($url))) {
			echo "\n❌ Ошибка: Не удалось скачать '$name'. Проверьте подключение и ID ресурса.";
			continue;
		}

		if (file_put_contents($savePath, $data) === false) {
			echo "\n❌ Ошибка: Не удалось сохранить '$name'. Проверьте права доступа.";
			continue;
		}

		echo "\n✅ Вирион '$name' успешно скачан!";
	}

	echo "\n✅ Все вирионы успешно скачаны!";
}

function remove(string $dir, array $skip = []): void
{
	if (!is_dir($dir)) return;

	foreach (array_diff(scandir($dir), [".", ".."], $skip) as $item) {
		$path = "$dir/$item";
		is_dir($path) ? remove($path) : unlink($path);
	}

	@rmdir($dir);
}

function recursiveCopy(string $source, string $destination): void
{
	if (!is_dir($source)) {
		echo "\n❌ Ошибка: Исходная директория '$source' не существует.";
		return;
	}

	@mkdir($destination, 0777, true);

	foreach (array_diff(scandir($source), [".", ".."]) as $item) {
		$srcPath = "$source/$item";
		$destPath = "$destination/$item";
		is_dir($srcPath) ? recursiveCopy($srcPath, $destPath) : copy($srcPath, $destPath);
	}
}

function extractVirions(): void
{
	global $path;

	echo "\n📦 Начало распаковки вирионов...";

	foreach (glob("$path/*.phar") as $pharFile) {
		$name = basename($pharFile, ".phar");
		$destination = "$path/$name";

		echo "\n⚡ Распаковываю вирион '$name'...";

		try {
			$phar = new Phar($pharFile);
			$phar->extractTo($destination);
		} catch (Exception $e) {
			echo "\n❌ Ошибка при распаковке '$name': " . $e->getMessage();
			remove($destination);

			if (file_exists($pharFile)) {
				unlink($pharFile);
			}

			continue;
		}

		unlink($pharFile);
		remove($destination, ["src"]);
		recursiveCopy("$destination/src", $path);
		remove($destination);
	}

	echo "\n✅ Все вирионы успешно распакованы!";
}

function ending(): void
{
	global $path;

	$autoload = __DIR__ . "/.virions_autoload.php";
	copy($autoload, $path . "/autoload.php");
}

downloadVirions();
extractVirions();
ending();