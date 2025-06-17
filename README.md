# TYPO3 DDEV Commands Collection

A specialized collection of DDEV commands for TYPO3 projects (version 12.4+).

This fork of the original [DDEV Commands Collection](https://github.com/jackd248/ddev-commands-collection) focuses exclusively on TYPO3 and provides optimized workflows for modern TYPO3 development.

## Features

The TYPO3 DDEV Commands Collection (DCC) automates recurring tasks in TYPO3 development. After installation, extended DDEV commands are available that combine multiple individual steps into efficient workflows.

### Available Commands

| Command                    | Description                                               | Example                              |
|--------------------------- |-----------------------------------------------------------|--------------------------------------|
| `ddev init`                | Initializes a complete TYPO3 installation                 | `ddev init`                          |
| `ddev cc`                  | Clears cache (short form)                                 | `ddev cc` or `ddev cc -g all`        |
| `ddev console`             | Executes TYPO3 console commands                           | `ddev console cache:flush`           |
| `ddev composer:app`        | Runs Composer in the app directory                        | `ddev composer:app install`          |
| `ddev composer:deployment` | Runs Composer in the deployment directory                 | `ddev composer:deployment update`    |
| `ddev sync`                | Synchronizes the database from the remote system          | `ddev sync stage`                    |
| `ddev theme`               | Builds frontend assets                                    | `ddev theme` or `ddev theme watch`   |
| `ddev log:app`             | Displays application log (with color coding)              | `ddev log:app -f`                    |
| `ddev release`             | Creates a new version                                     | `ddev release 1.2.3`                 |


## Installation

### Step 1: Prepare Composer

Add the following entries to your `composer.json` in the project root:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/n0vedad/ddev-commands-collection"
        }
    ]
}
```

### Step 2: Install DCC

```bash
ddev composer require --dev kmi/ddev-commands-collection
```

## Usage

After installation, all commands are immediately available. A typical workflow for a new project:

```bash
# Initialize TYPO3, sync database and build assets
ddev init
```
For other individual steps see Available Commands.

## Configuration

### .ddev/commands/dcc-config.sh

This file contains paths and default values:

```bash
# Path to TYPO3 installation
composerPathApp="/var/www/html"

# Default system for database sync
defaultSyncSystem="stage"

# Log path for log:app command
logPathApp="/var/www/html/var/log/"
```

### .ddev/commands/dcc-config.yaml

Exclude commands from automatic updates:

```yaml
ignoreFiles:
  - web/dcc-custom-command
  - host/dcc-special-script
```

## Protecting Custom Commands

To protect your own DDEV commands from being overwritten, add one of the following comments to your file:

```bash
## <keep/>
## <ignore/>
## <custom/>
```

## Requirements

- TYPO3 12.4 or higher
- DDEV (tested with version 1.21+)
- PHP 8.1 or higher
- Composer 2.0 or higher

## Troubleshooting

### "ddev: command not found"

Ensure DDEV is correctly installed. See [DDEV Documentation](https://ddev.readthedocs.io/).

### Commands are not copied

Check if the Composer scripts are correctly entered in your `composer.json` (see Step 1).

### Database synchronization fails

The `ddev sync` command requires `helhum/typo3-console` to function properly. This dependency is automatically installed with the DCC package. If you encounter sync issues, verify that the package is installed by running:

```bash
ddev composer show helhum/typo3-console
```

If the package is missing, reinstall the DCC:

```bash
ddev composer reinstall kmi/ddev-commands-collection
```

### Changed commands are overwritten

Add `## <keep/>` to your customized commands to protect them from updates.

## License

MIT License - see [LICENSE](LICENSE) file.

## Credits

Based on the work of [Konrad Michalik](https://github.com/jackd248) and his [DDEV Commands Collection](https://github.com/jackd248/ddev-commands-collection) project.