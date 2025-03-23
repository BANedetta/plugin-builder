import os
import shutil
import requests
from subprocess import run
from pathlib import Path

path = Path.cwd() / "virions"

def remove(directory: str, skip: list = []):
	directory = Path(directory)

	if not directory.is_dir():
		return

	for item in directory.iterdir():
		if item.name in skip:
			continue
		if item.is_dir():
			remove(item)
		else:
			item.unlink()

	# Проверяем, пуста ли папка после удаления пропущенных файлов
	if not any(directory.iterdir()):
		directory.rmdir()

def recursive_copy(source: str, destination: str):
	source = Path(source)
	destination = Path(destination)
	if not source.is_dir():
		print(f"\n❌ Ошибка: Исходная директория '{source}' не существует.")
		return

	destination.mkdir(parents=True, exist_ok=True)

	for item in source.iterdir():
		dest_path = destination / item.name
		if item.is_dir():
			recursive_copy(item, dest_path)
		else:
			shutil.copy2(item, dest_path)

def download_virions():
	virions_file = Path(os.getcwd()) / "virions.txt"

	if not virions_file.exists():
		print("\n❌ Ошибка: Файл 'virions.txt' отсутствует.")
		return

	with virions_file.open("r", encoding="utf-8") as f:
		virions = [line.strip() for line in f if line.strip()]

	if not virions:
		print("\n❌ Ошибка: Файл 'virions.txt' пуст.")
		return

	print("\n📥 Начало скачивания вирионов...")

	for virion in virions:
		try:
			id_, name = map(str.strip, virion.split(";"))
			save_path = path / f"{name}.phar"
			url = f"https://poggit.pmmp.io/r/{id_}"

			print(f"\n📥 Скачиваю вирион '{name}' (ID: {id_}) с {url}...")
			response = requests.get(url)
			response.raise_for_status()

			with save_path.open("wb") as f:
				f.write(response.content)

			print(f"\n✅ Вирион '{name}' успешно скачан!")
		except Exception as e:
			print(f"\n❌ Ошибка при скачивании '{name}': {e}")
			continue

	print("\n✅ Все вирионы успешно скачаны!")

def extract_virions():
	path = Path(os.getcwd()) / "virions"
	print("\n📦 Начало распаковки вирионов...")

	for phar_file in path.glob("*.phar"):
		name = phar_file.stem
		destination = path / name

		print(f"\n⚡ Распаковываю вирион '{name}'...")
		try:
			command = ["php", "-r", f"$phar = new Phar('{phar_file}'); $phar->extractTo('{destination}');"]
			run(command)

			phar_file.unlink()
			remove(destination, ["src"])
			recursive_copy(destination / "src", path)
			remove(destination)

			print(f"\n✅ Вирион '{name}' успешно распакован!")
		except Exception as e:
			print(f"\n❌ Ошибка при распаковке '{name}': {e}")
			remove(destination)

			if phar_file.exists():
				phar_file.unlink()

	print("\n✅ Все вирионы успешно распакованы!")

def final():
	autoload = Path.cwd() / ".virions_autoload.php"
	shutil.copy2(autoload, path / "autoload.php")

if path.is_dir():
	remove(path)

path.mkdir(exist_ok=True)

if __name__ == "__main__":
	download_virions()
	extract_virions()
	final()