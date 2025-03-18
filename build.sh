#!/bin/bash

# Default flags
USE_COMPOSER=false
USE_VIRIONS=false

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Argument parsing
while getopts "cv" opt; do
	case ${opt} in
		c ) USE_COMPOSER=true ;;
		v ) USE_VIRIONS=true ;;
		* ) echo -e "${RED}Usage: $0 [-c] [-v]${NC}"
		exit 1 ;;
	esac
done

echo -e "${BLUE}Using Composer: $USE_COMPOSER${NC}"
echo -e "${BLUE}Using Virions: $USE_VIRIONS${NC}"

# Check for required tools
for cmd in php composer git unzip curl jq yq; do
	if ! command -v $cmd &> /dev/null; then
		echo -e "${RED}Error: $cmd is not installed. Please install it and try again.${NC}" >&2
		exit 1
	fi
done

# Enable Phar writing
PHP_INI_PATH=$(php -i | grep 'Loaded Configuration File' | awk '{print $NF}')
echo -e "${BLUE}Using php.ini: $PHP_INI_PATH${NC}"
echo "phar.readonly=0" >> "$PHP_INI_PATH"

# Clone scripts from template repository
git clone --depth=1 https://github.com/Taskov1ch/pmmp-plugin-builder.git temp_repo
cp -r temp_repo/scripts .
rm -rf temp_repo

# Build virions if required and virions.yml exists
if [[ "$USE_VIRIONS" == "true" && -f "virions.yml" ]]; then
	echo -e "${BLUE}virions.yml found, proceeding...${NC}"

	mkdir -p virions

	# Download and build virions
	yq e '.[] | .repo + " " + .src' virions.yml | while read -r repo src; do
		echo -e "${BLUE}Processing virion: $repo...${NC}"

		# Check if the repository exists
		repo_info=$(curl -s "https://api.github.com/repos/$repo")
		if echo "$repo_info" | jq -e '.message == "Not Found"' &>/dev/null; then
			echo -e "${YELLOW}Warning: Repository $repo not found, skipping...${NC}"
			continue
		fi

		branch=$(echo "$repo_info" | jq -r '.default_branch // "main"')
		repo_url="https://github.com/$repo/archive/refs/heads/$branch.zip"
		curl -L "$repo_url" -o repo.zip
		unzip repo.zip -d repo && rm repo.zip
		extracted_path=$(find repo -mindepth 1 -maxdepth 1 -type d | head -n 1)/"$src"

		if [ ! -d "$extracted_path" ]; then
			echo -e "${RED}Error: Directory $src not found in $repo, skipping...${NC}" >&2
			rm -rf repo
			continue
		fi

		cp scripts/virion_build.php "$extracted_path/build.php"
		(cd "$extracted_path" && php build.php)

		if [ ! -f "$extracted_path/virion.phar" ]; then
			echo -e "${RED}Error: virion.phar not created for $repo, skipping...${NC}" >&2
			rm -rf repo
			continue
		fi

		mv "$extracted_path/virion.phar" "virions/$(basename "$repo").phar"
		rm -rf repo
	done
else
	echo -e "${YELLOW}Virions are not required or virions.yml not found.${NC}"
fi

# Install dependencies via Composer if required
if [[ "$USE_COMPOSER" == "true" ]]; then
	if [[ -f "composer.json" ]]; then
		composer install --no-dev --optimize-autoloader
	else
		echo -e "${YELLOW}Warning: composer.json not found.${NC}"
	fi
fi

# Build the plugin
cp scripts/plugin_build.php ./build.php
php build.php

# Verify plugin.phar exists
if [ -f "plugin.phar" ]; then
	echo -e "${GREEN}Build completed. plugin.phar created.${NC}"
else
	echo -e "${RED}Error: plugin.phar was not created!${NC}" >&2
	exit 1
fi

# Cleanup
rm -rf build.php
rm -rf scripts
rm -rf virions