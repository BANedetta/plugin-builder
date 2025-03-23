"""
Мне легче работать в Python, так что не вам меня судить
"""

import re
from pathlib import Path

# Пути
PLUGIN_PATH = Path(__file__).parent
AUTOLOAD_PATH = "autoload.php"
THREAD_CLASSES = [
	"Thread", "AsyncTask"
]
IGNORE_PATHES = [
	"vendor"
]
EXTRA_UP = {
	"virions": 1
}

def get_php_files(directory: Path):
	"""Получает список всех PHP файлов в указанной директории."""
	return list(directory.rglob("*.php"))

def get_class_from_file(file: Path):
	"""Извлекает информацию о классе из PHP файла."""
	try:
		content = file.read_text(encoding="utf-8")
	except Exception as e:
		print(f"❌ Ошибка чтения файла {file}: {e}")
		return None

	namespace_match = re.search(r'namespace\s+([^;]+);', content)
	namespace = namespace_match.group(1).strip() if namespace_match else ''

	class_match = re.search(r'class\s+(\w+)\s+extends\s+(\w+)', content)

	if class_match:
		class_name, parent_class = class_match.groups()
		full_class = f"{namespace}.{class_name}" if namespace else class_name

		return {
			"full_class": full_class,
			"parent_class": parent_class,
			"file": file
		}

	return None

def modify_on_run_method(file: Path, autoload_path: str):
	"""Добавляет require_once в метод onRun, если его еще нет."""
	try:
		content = file.read_text(encoding="utf-8")
	except Exception as e:
		print(f"❌ Ошибка чтения файла {file}: {e}")
		return

	if "require_once" in content:
		print(f"❌ Пропущено (require_once уже присутствует): {file}")
		return

	match = re.search(r'(?:public|protected)?\s*function\s+onRun\s*\(\s*\)\s*(?::\s*\w+)?\s*{?', content, re.MULTILINE)

	if match:
		insert_pos = match.end()
		new_content = content[:insert_pos] + f'\n\t\trequire_once __DIR__ . "/{autoload_path}";\n' + content[insert_pos:]

		try:
			file.write_text(new_content, encoding="utf-8")
			print(f"✅ Изменен файл: {file}")
		except Exception as e:
			print(f"❌ Ошибка записи в файл {file}: {e}")
	else:
		print(f"⚠️ Не найден метод onRun в файле: {file}")

# Обработка файлов
php_files = get_php_files(PLUGIN_PATH)
print(f"🔍 Найдено {len(php_files)} PHP файлов.")

for file in php_files:
	class_data = get_class_from_file(file)

	if class_data and class_data["parent_class"] in THREAD_CLASSES:
		# Пропустить файлы, если путь содержит одну из игнорируемых папок
		if any(ignored in file.parts for ignored in IGNORE_PATHES):
			print(f"🚫 Пропущен (игнорируемая папка): {file}")
			continue

		namespace_parts = class_data["full_class"].split("\\")

		# Вычисляем дополнительные уровни up на основе всех папок из EXTRA_UP
		extra_up = sum(EXTRA_UP.get(part, 0) for part in file.parts)

		levels_up = len(namespace_parts) + extra_up
		autoload_path = ("../" * levels_up) + AUTOLOAD_PATH

		modify_on_run_method(file, autoload_path)

print("🎉 Готово!")
