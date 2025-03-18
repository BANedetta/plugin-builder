```
# PocketMine-MP Plugin Builder

This repository provides an automated tool for building PocketMine-MP plugins in `.phar` format. It includes support for automatic dependency installation via `Composer` and `virions` compilation if a `virions.yml` file is present.

## Features
- Automatic dependency installation via `Composer` (if enabled)
- Support for `virions` compilation (if enabled and `virions.yml` is available)
- Generation of a `.phar` archive ready for installation on a PocketMine-MP server
- Integration with GitHub Actions for automated builds

## Installation and Usage

### Requirements
Before you begin, make sure you have the following tools installed:
- `PHP` (version 8.2 or higher)
- `Composer`
- `Git`
- `unzip`
- `curl`
- `jq`
- `yq`

### Local Build

1. Clone the repository:
   ```sh
   git clone https://github.com/Taskov1ch/pmmp-plugin-builder.git builder
   cd builder
   ```

2. Run the build script with the required parameters:
   ```sh
   chmod +x build.sh
   ./build.sh -c -v
   ```

   Options:
   - `-c` — use Composer to install dependencies
   - `-v` — build virions if `virions.yml` is present

3. After successful execution, the `plugin.phar` file will be created, ready for use on the server.

### Using Virions
To install `virions`, you need a `virions.yml` file in the root of your project with the following format:
```yaml
- repo: username/repo
  src: path/to/directory/where/virion.yml/is/located/or/leave/empty/if/virion.yml/is/in/the/root
```
Example:
```yaml
- repo: BANedetta/libasynql # repository name
  src: libasynql # directory where virion.yml is located
```

### Automated Build with GitHub Actions

This project supports automated builds using GitHub Actions. To enable it, add the following workflow file to your repository:
```yaml
name: Build PocketMine-MP Plugin

on: [push]

jobs:
  build:
    uses: Taskov1ch/pmmp-plugin-builder/.github/workflows/build.yml@main # reference to the reusable workflow
    with:
      php_version: '8.2' # PHP version
      use_composer: true # use Composer to install dependencies
      use_virions: true # use virions for plugin compilation
```
After each commit to the repository, the plugin will be automatically built and uploaded to GitHub Actions artifacts.

## Conclusion
This tool simplifies the process of building PocketMine-MP plugins by eliminating the need for manual dependency installation and `virions` compilation. Just configure `virions.yml`, add `composer.json`, and your plugin will be ready for use with minimal effort.
```