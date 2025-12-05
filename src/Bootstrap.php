<?php
/**
 * Application Bootstrap
 * 
 * This file contains the application initialization logic.
 */

declare(strict_types=1);

namespace PickingReport;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

class Bootstrap
{
    private static ?Logger $logger = null;

    /**
     * Initialize the application
     */
    public static function init(): void
    {
        self::initializeLogger();
    }

    /**
     * Initialize the logger
     */
    private static function initializeLogger(): void
    {
        $logLevel = self::getLogLevel($_ENV['LOG_LEVEL'] ?? 'debug');
        $logPath = $_ENV['LOG_PATH'] ?? BASE_PATH . '/logs/app.log';
        $maxFiles = (int)($_ENV['LOG_MAX_FILES'] ?? 7);

        self::$logger = new Logger('picking-report');
        self::$logger->pushHandler(
            new RotatingFileHandler($logPath, $maxFiles, $logLevel)
        );
    }

    /**
     * Get the logger instance
     */
    public static function getLogger(): Logger
    {
        if (self::$logger === null) {
            self::initializeLogger();
        }
        return self::$logger;
    }

    /**
     * Convert log level string to Monolog constant
     */
    private static function getLogLevel(string $level): int
    {
        return match (strtolower($level)) {
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'notice' => Logger::NOTICE,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
            'alert' => Logger::ALERT,
            'emergency' => Logger::EMERGENCY,
            default => Logger::DEBUG,
        };
    }
}
