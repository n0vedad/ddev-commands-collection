# TYPO3 DDEV Commands Collection

A specialized collection of DDEV commands for TYPO3 projects (version 12.4+).

This fork of the original [DDEV Commands Collection](https://github.com/jackd248/ddev-commands-collection) focuses exclusively on TYPO3 and provides optimized workflows for modern TYPO3 development.

## Features

The TYPO3 DDEV Commands Collection (DCC) automates recurring tasks in TYPO3 development. After installation, extended DDEV commands are available that combine multiple individual steps into efficient workflows.

### Available Commands

| Befehl                    | Beschreibung                                      | Beispiel                            |
|---------------------------|---------------------------------------------------|-------------------------------------|
| `ddev init`               | Initialisiert eine komplette TYPO3-Installation   | `ddev init`                         |
| `ddev cc`                 | Cache leeren (Kurzform)                           | `ddev cc` oder `ddev cc -g all`     |
| `ddev console`            | TYPO3 Console-Befehle ausführen                   | `ddev console cache:flush`          |
| `ddev composer:app`       | Composer im App-Verzeichnis ausführen             | `ddev composer:app install`         |
| `ddev composer:deployment`| Composer im Deployment-Verzeichnis ausführen      | `ddev composer:deployment update`   |
| `ddev sync`               | Datenbank vom Remote-System synchronisieren       | `ddev sync stage`                   |
| `ddev theme`              | Frontend-Assets bauen                             | `ddev theme` oder `ddev theme watch`|
| `ddev log:app`            | Applikations-Log anzeigen (mit Farbcodierung)     | `ddev log:app -f`                   |
| `ddev release`            | Neue Version erstellen                            | `ddev release 1.2.3`                |

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
    ],
    "scripts": {
        "post-install-cmd": [
            "Kmi\\DdevCommandsCollection\\Composer\\Scripts::updateCommands"
        ],
        "post-update-cmd": [
            "Kmi\\DdevCommandsCollection\\Composer\\Scripts::updateCommands"
        ]
    }
}
```

### Step 2: Install DCC

```bash
composer require --dev n0vedad/ddev-commands-collection
```

### Step 3: Optional Dependencies

For full functionality, install:

```bash
composer require --dev helhum/typo3-console
```

Note: typo3-console is required for database synchronization (`ddev sync`).

## Usage

After installation, all commands are available. A typical workflow for a new project:

```bash
# Initialize TYPO3, sync database and build assets
ddev init

# Individual steps:
ddev sync stage      # Get database from stage system
ddev theme           # Build frontend assets
ddev cc              # Clear cache
```

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

### "helhum/typo3-console is required but not installed"

This message appears with `ddev sync`. Install typo3-console as described in Step 3.

### Changed commands are overwritten

Add `## <keep/>` to your customized commands to protect them from updates.

## License

MIT License - see [LICENSE](LICENSE) file.

## Credits

Based on the work of [Konrad Michalik](https://github.com/jackd248) and his [DDEV Commands Collection](https://github.com/jackd248/ddev-commands-collection) project.