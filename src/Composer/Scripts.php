<?php declare(strict_types=1);

namespace Kmi\DdevCommandsCollection\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Scripts - TYPO3 specific version
 *
 * @author Konrad Michalik <hello@konradmichalik.eu>
 * @author Lucas Dämmig <lucas.daemmig@rasani.de>
 * @package Kmi\DdevCommandsCollection\Composer
 */
class Scripts implements PluginInterface, EventSubscriberInterface
{
    /**
     * Keywords that mark a file to be ignored during update
     */
    const IGNORE_KEYWORDS = [
        '<keep/>',
        '<ignore/>',
        '<custom/>'
    ];

    /**
     * @var Event
     */
    protected static $event;

    /**
     * @var array
     */
    protected static $extra;

    /**
     * @var Composer
     */
    protected static $composer;

    /**
     * @var IOInterface
     */
    protected static $io;

    /**
     * @var Filesystem
     */
    protected static $fs;

    /**
     * @var array
     */
    protected static $config = [
        'ignoreFiles' => []
    ];

    /**
     * Subscribe to composer events
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::PRE_PACKAGE_UNINSTALL => 'onPrePackageUninstall',
        ];
    }

    /**
     * Required by PluginInterface - called when plugin is activated
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        // Nothing special needed here for our use case
    }

    /**
     * Required by PluginInterface - called when plugin is deactivated
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing special needed here for our use case
    }

    /**
     * Required by PluginInterface - called when plugin is uninstalled
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Nothing special needed here for our use case
    }

    /**
     * Handle package uninstall event
     */
    public static function onPrePackageUninstall(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        
        if ($operation instanceof UninstallOperation) {
            $package = $operation->getPackage();
            
            if ($package->getName() === 'kmi/ddev-commands-collection') {
                static::$event = $event;
                static::$composer = $event->getComposer();
                static::$io = $event->getIO();
                static::$fs = new Filesystem();
                
                if (static::initConfig() === 0) {
                    static::removeFiles();
                }
            }
        }
    }

    /**
     * Initialize scripts
     *
     * @param Event $event
     * @return int
     * @throws \Exception
     */
    protected static function init(Event $event): int
    {
        /** @var Event event */
        static::$event = $event;
        /** @var Composer composer */
        static::$composer = $event->getComposer();
        /** @var array extra */
        static::$extra = static::$composer->getPackage()->getExtra();
        /** @var IOInterface io */
        static::$io = $event->getIO();
        /** @var Filesystem fs */
        static::$fs = new Filesystem();

        return self::initConfig();
    }

    /**
     * Initialize configuration
     *
     * @return int
     * @throws \Exception
     */
    private static function initConfig(): int
    {
        static::$config['distDir'] = dirname(dirname(__DIR__)) . '/src/CommandsCollection';

        static::$config['ddevDir'] = static::$composer->getConfig()->get('ddev-dir') 
            ? './' . static::$composer->getConfig()->get('ddev-dir') 
            : './.ddev';
            
        if (!is_dir(static::$config['ddevDir'])) {
            static::$io->write(sprintf('<fg=red>[DCC]</> DDEV directory "%s" doesn\'t exist', static::$config['ddevDir']));
            return 1;
        }

        $configFilePath = static::$config['ddevDir'] . '/commands/dcc-config.yaml';
        if (file_exists($configFilePath)) {
            $configFile = Yaml::parse(file_get_contents($configFilePath));
            if (!is_null($configFile)) {
                static::$config = array_merge(static::$config, $configFile);
            }
        }

        return 0;
    }

    /**
     * Update ddev commands (if necessary)
     *
     * @param Event $event
     * @throws DCCException|\Exception
     */
    public static function updateCommands(Event $event): void
    {
        $statusCode = static::init($event);
        if (!$statusCode) {
            static::copyFiles();
        }
    }

    /**
     * Remove the TYPO3 command files from the project (reverse of copyFiles)
     */
    protected static function removeFiles(): void
    {
        static::$io->write('<fg=cyan>[DCC]</> Remove <options=bold>TYPO3 DDEV</> command files from project', false);
        $countRemoved = 0;

        // Check for custom commands to ignore (same logic as in copyFiles)
        $commandsPath = static::$config['ddevDir'] . '/commands/';
        $files = glob($commandsPath . '*/dcc-*');
        $files[] = $commandsPath . 'dcc-config.sh';
        
        foreach($files as $filename) {
            if(is_file($filename)) {
                $fileContent = file_get_contents($filename);
                $shouldFileBeIgnored = false;
                
                foreach (self::IGNORE_KEYWORDS as $keyword) {
                    $shouldFileBeIgnored = (bool)strpos($fileContent, $keyword);
                    if ($shouldFileBeIgnored) break;
                }
                
                if ($shouldFileBeIgnored) {
                    static::$config['ignoreFiles'][] = str_replace($commandsPath, '', $filename);
                }
            }
        }

        // Remove TYPO3 specific command files (reverse of what copyFiles does)
        $distCommands = static::$config['distDir'] . '/typo3/';
        
        $files = glob($distCommands . '*/dcc-*');
        $files[] = $distCommands . 'dcc-config.sh';
        
        foreach($files as $fullPathFilename) {
            $relativePathFilename = str_replace($distCommands, '', $fullPathFilename);
            
            if (is_null(static::$config['ignoreFiles']) || !in_array($relativePathFilename, static::$config['ignoreFiles'])) {
                $targetFilePath = static::$config['ddevDir'] . '/commands/' . $relativePathFilename;
                
                if (file_exists($targetFilePath)) {
                    // Remove symlink or regular file
                    if (is_link($targetFilePath)) {
                        unlink($targetFilePath);
                    } else {
                        static::$fs->remove($targetFilePath);
                    }
                    $countRemoved++;
                }
            }
        }

        // Remove static general files (reverse of copyFiles)
        $generalStaticFiles = [
            'scripts',
            'faq', 
            'README.md',
            '.gitignore'
        ];
        
        foreach ($generalStaticFiles as $item) {
            $itemPath = static::$config['ddevDir'] . '/commands/' . $item;
            if (file_exists($itemPath)) {
                static::$fs->remove($itemPath);
                $countRemoved++;
            }
        }

        // Remove host commands
        $hostCommandPath = static::$config['ddevDir'] . '/commands/host/dcc-docker-deployment-update';
        if (file_exists($hostCommandPath)) {
            static::$fs->remove($hostCommandPath);
            $countRemoved++;
        }

        $countIgnored = is_null(static::$config['ignoreFiles']) ? 0 : count(static::$config['ignoreFiles']);
        $infoIgnored = is_null(static::$config['ignoreFiles']) ? '' : implode(', ', static::$config['ignoreFiles']);

        $infoMessage = "<fg=green>$countRemoved</> file(s) removed";
        if ($countIgnored) {
            $infoMessage .= ", <fg=yellow>$countIgnored</> file(s) kept: $infoIgnored";
        }

        static::$io->write(" ($infoMessage)");
    }

    /**
     * Copy the TYPO3 command files to the project
     */
    protected static function copyFiles(): void
    {
        static::$io->write('<fg=cyan>[DCC]</> Copy <options=bold>TYPO3 DDEV</> command files to project', false);
        $countCopied = 0;

        // Copy initial general files
        static::$fs->mirror(
            static::$config['distDir'] . '/general/initial',
            static::$config['ddevDir'] . '/commands',
            null,
            ['override' => false]
        );

        // Copy static general files
        static::$fs->mirror(
            static::$config['distDir'] . '/general/static',
            static::$config['ddevDir'] . '/commands',
            null,
            ['override' => true]
        );

        // Check for custom commands to ignore
        $commandsPath = static::$config['ddevDir'] . '/commands/';
        $files = glob($commandsPath . '*/dcc-*');
        $files[] = $commandsPath . 'dcc-config.sh';
        
        foreach($files as $filename) {
            if(is_file($filename)) {
                $fileContent = file_get_contents($filename);
                $shouldFileBeIgnored = false;
                
                foreach (self::IGNORE_KEYWORDS as $keyword) {
                    $shouldFileBeIgnored = (bool)strpos($fileContent, $keyword);
                    if ($shouldFileBeIgnored) break;
                }
                
                if ($shouldFileBeIgnored) {
                    static::$config['ignoreFiles'][] = str_replace($commandsPath, '', $filename);
                }
            }
        }

        // Copy TYPO3 specific command files
        $distCommands = static::$config['distDir'] . '/typo3/';
        
        $files = glob($distCommands . '*/dcc-*');
        $files[] = $distCommands . 'dcc-config.sh';
        
        foreach($files as $fullPathFilename) {
            $relativePathFilename = str_replace($distCommands, '', $fullPathFilename);
            
            if (is_null(static::$config['ignoreFiles']) || !in_array($relativePathFilename, static::$config['ignoreFiles'])) {
                $targetFilePath = static::$config['ddevDir'] . '/commands/' . $relativePathFilename;
                
                static::$fs->copy(
                    $fullPathFilename,
                    $targetFilePath,
                    true
                );
                $countCopied++;
                
                $fileContents = file_get_contents($targetFilePath);
                $fileContents = str_replace("<version/>", static::getVersion(), $fileContents);
                file_put_contents($targetFilePath, $fileContents);
            }
        }

        $countIgnored = is_null(static::$config['ignoreFiles']) ? 0 : count(static::$config['ignoreFiles']);
        $infoIgnored = is_null(static::$config['ignoreFiles']) ? '' : implode(', ', static::$config['ignoreFiles']);

        $infoMessage = "<fg=green>$countCopied</> file(s) copied";
        if ($countIgnored) {
            $infoMessage .= ", <fg=yellow>$countIgnored</> file(s) ignored: $infoIgnored";
        }

        static::$io->write(" ($infoMessage)");
    }

    /**
     * Get package version from composer.json
     *
     * @return string
     */
    protected static function getVersion(): string
    {
        $composerFile = dirname(dirname(__DIR__)) . '/composer.json';
        return (string)\json_decode(file_get_contents($composerFile), true)['version'];
    }
}