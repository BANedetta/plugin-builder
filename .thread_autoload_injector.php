<?php

function getPhpFiles(string $directory): array
{
	$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
	$files = [];

	foreach ($rii as $file) {
		if ($file->isDir() || $file->getExtension() !== "php") {
			continue;
		}

		$files[] = $file->getPathname();
	}

	return $files;
}

function getClassFromFile(string $file): ?array
{
	$content = file_get_contents($file);
	if ($content === false) {
		echo "❌ Ошибка чтения файла $file\n";
		return null;
	}

	preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch);
	$namespace = $namespaceMatch[1] ?? '';

	preg_match('/class\s+(\w+)\s+extends\s+(\w+)/', $content, $classMatch);
	if ($classMatch) {
		[$fullMatch, $className, $parentClass] = $classMatch;
		$fullClass = $namespace ? "$namespace\\$className" : $className;
		return ['full_class' => $fullClass, 'parent_class' => $parentClass, 'file' => $file];
	}

	return null;
}

function modifyOnRunMethod(string $file, string $autoloadPath): void
{
	$content = file_get_contents($file);
	if ($content === false) {
		echo "❌ Ошибка чтения файла $file\n";
		return;
	}

	if (strpos($content, 'require_once') !== false) {
		echo "❌ Пропущено (require_once уже присутствует): $file\n";
		return;
	}

	if (preg_match('/(?:public|protected)?\s*function\s+onRun\s*\(\s*\)\s*(?::\s*\w+)?\s*{?/', $content, $matches, PREG_OFFSET_CAPTURE)) {
		$insertPos = $matches[0][1] + strlen($matches[0][0]);
		$newContent = substr_replace($content, "\n\t\trequire_once __DIR__ . '/$autoloadPath';\n", $insertPos, 0);

		if (file_put_contents($file, $newContent) !== false) {
			echo "✅ Изменен файл: $file\n";
		} else {
			echo "❌ Ошибка записи в файл $file\n";
		}
	} else {
		echo "⚠️ Не найден метод onRun в файле: $file\n";
	}
}

$pluginPath = __DIR__;
$autoloadPath = "autoload.php";
$threadClasses = ["Thread", "AsyncTask"];
$ignorePaths = ["vendor"];
$extraUp = ["virions" => 1];

$phpFiles = getPhpFiles($pluginPath);
echo "🔍 Найдено " . count($phpFiles) . " PHP файлов.\n";

foreach ($phpFiles as $file) {
	$classData = getClassFromFile($file);
	if ($classData && in_array($classData['parent_class'], $threadClasses, true)) {
		if (array_intersect(explode(DIRECTORY_SEPARATOR, $file), $ignorePaths)) {
			echo "🚫 Пропущен (игнорируемая папка): $file\n";
			continue;
		}

		$namespaceParts = explode("\\", $classData["full_class"]);
		$extraUpLevels = array_sum(array_intersect_key($extraUp, array_flip(explode(DIRECTORY_SEPARATOR, $file))));
		$levelsUp = count($namespaceParts) + $extraUpLevels;
		$relativeAutoloadPath = str_repeat("../", (int) $levelsUp) . $autoloadPath;

		modifyOnRunMethod($file, $relativeAutoloadPath);
	}
}

echo "🎉 Готово!\n";
