#!/bin/bash

# Default flags
USE_COMPOSER=false
USE_VIRIONS=false

# Argument parsing
while getopts "c:v:" opt; do
  case ${opt} in
    c ) USE_COMPOSER=$OPTARG ;;
    v ) USE_VIRIONS=$OPTARG ;;
    * ) echo "Usage: $0 [-c use_composer] [-v use_virions]"
        exit 1 ;;
  esac
done

echo "Using Composer: $USE_COMPOSER"
echo "Using Virions: $USE_VIRIONS"

# Check for required tools
for cmd in php composer git unzip curl jq yq; do
  if ! command -v $cmd &> /dev/null; then
    echo "Error: $cmd is not installed. Please install it and try again." >&2
    exit 1
  fi
done

# Enable Phar writing
PHP_INI_PATH=$(php -i | grep 'Loaded Configuration File' | awk '{print $NF}')
echo "Using php.ini: $PHP_INI_PATH"
echo "phar.readonly=0" >> "$PHP_INI_PATH"

# Clone scripts from template repository
git clone --depth=1 https://github.com/Taskov1ch/pmmp-plugin-builder.git temp_repo
cp -r temp_repo/scripts .
rm -rf temp_repo

# Build virions if required and virions.yml exists
if [[ "$USE_VIRIONS" == "true" && -f "virions.yml" ]]; then
  echo "virions.yml found, proceeding..."

  mkdir -p virions

  # Download and build virions
  yq e '.[] | .repo + " " + .src' virions.yml | while read -r repo src; do
    echo "Processing virion: $repo..."

    # Check if the repository exists
    repo_info=$(curl -s "https://api.github.com/repos/$repo")
    if echo "$repo_info" | jq -e '.message == "Not Found"' &>/dev/null; then
      echo "Warning: Repository $repo not found, skipping..."
      continue
    fi

    branch=$(echo "$repo_info" | jq -r '.default_branch // "main"')
    repo_url="https://github.com/$repo/archive/refs/heads/$branch.zip"
    curl -L "$repo_url" -o repo.zip
    unzip repo.zip -d repo && rm repo.zip
    extracted_path=$(find repo -mindepth 1 -maxdepth 1 -type d | head -n 1)/"$src"

    if [ ! -d "$extracted_path" ]; then
      echo "Error: Directory $src not found in $repo, skipping..." >&2
      rm -rf repo
      continue
    fi

    cp scripts/virion_build.php "$extracted_path/build.php"
    (cd "$extracted_path" && php build.php)

    if [ ! -f "$extracted_path/virion.phar" ]; then
      echo "Error: virion.phar not created for $repo, skipping..." >&2
      rm -rf repo
      continue
    fi

    mv "$extracted_path/virion.phar" "virions/$(basename "$repo").phar"
    rm -rf repo
  done
else
  echo "Virions are not required or virions.yml not found."
fi

# Install dependencies via Composer if required
if [[ "$USE_COMPOSER" == "true" ]]; then
  if [[ -f "composer.json" ]]; then
    composer install --no-dev --optimize-autoloader
  else
    echo "Warning: composer.json not found."
  fi
fi

# Build the plugin
cp scripts/plugin_build.php ./build.php
php build.php

# Verify plugin.phar exists
if [ -f "plugin.phar" ]; then
  echo "Build completed. plugin.phar created."
else
  echo "Error: plugin.phar was not created!" >&2
  exit 1
fi
