import os
import re
import yaml

def find_namespace_and_insert_require(plugin_yml_path, src_path):
	try:
		# Читаем plugin.yml
		with open(plugin_yml_path, "r", encoding="utf-8") as file:
			config = yaml.safe_load(file)
	except FileNotFoundError:
		print("❌ Файл plugin.yml не найден.")
		return
	except yaml.YAMLError as e:
		print(f"⚠️ Ошибка парсинга YAML: {e}")
		return

	# Получаем путь к главному классу
	main_class_path = config.get("main", "").replace("\\", "/") + ".php"
	class_file_path = os.path.join(src_path, main_class_path)

	if not os.path.exists(class_file_path):
		print(f"❌ Файл {class_file_path} не найден.")
		return

	try:
		with open(class_file_path, "r", encoding="utf-8") as file:
			content = file.readlines()
	except Exception as e:
		print(f"⚠️ Ошибка чтения файла: {e}")
		return

	namespace = None
	namespace_index = None

	for i, line in enumerate(content):
		match = re.match(r"namespace\s+([\w\\]+);", line)

		if match:
			namespace = match.group(1).split("\\")
			namespace_index = i
			break

	if not namespace:
		print("❌ Namespace не найден.")
		return

	# Генерируем require_once строки
	require_depth = len(namespace) + 1
	requires = f"\nrequire_once __DIR__ . '{'/..' * require_depth}/autoload.php';\n"

	# Проверяем, есть ли уже require_once
	if any("require_once" in line for line in content):
		print("✅ require_once уже присутствует.")
		return

	# Вставляем require_once после namespace
	content.insert(namespace_index + 1, requires)

	try:
		with open(class_file_path, "w", encoding="utf-8") as file:
			file.writelines(content)
		print(f"🎉 Файл {class_file_path} успешно обновлен!")
	except Exception as e:
		print(f"⚠️ Ошибка записи в файл: {e}")

plugin_yml_path = "plugin.yml"
src_path = "src"

# Использование
if __name__ == "__main__":
	find_namespace_and_insert_require(plugin_yml_path, src_path)
