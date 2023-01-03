<?php

namespace Syncrasy\PimcoreSalesforceBundle\Lib;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

/**
 * Class Logger
 * @package Syncrasy\PimcoreSalesforceBundle\Lib\Logger
 */
class PSCLogger
{ 
    /** const string - log source */
    const SOURCE = 'PSC';
    
    /** const string - name of the log file */
    const DEFAULT_LOG_NAME = 'PSC.log';

    /** const int - default log level */
    const DEFAULT_LOG_LEVEL = Logger::DEBUG;

    /** const int - default max rotational files */
    const MAX_ROTATIONAL_FILES = 10;

    /** log levels */
    const DEBUG = Logger::DEBUG;
    const INFO = Logger::INFO;
    const NOTICE = Logger::NOTICE;
    const WARNING = Logger::WARNING;
    const ERROR = Logger::ERROR;
    const CRITICAL = Logger::CRITICAL;
    const ALERT = Logger::ALERT;
    const EMERGENCY = Logger::EMERGENCY;
 
    public static function getMonoLogger($logName = null, $logLevel = null, array $config = [])
    {
        $monoLogger = new Logger($logName);

        $logName = empty($logName) ? self::DEFAULT_LOG_NAME : $logName;
        $logFullPath = PIMCORE_LOG_DIRECTORY . DIRECTORY_SEPARATOR . $logName;
        $logLevel = empty($logLevel) ? self::DEFAULT_LOG_LEVEL : $logLevel;

        // Setup the main handler
        if (!empty($config) && $config['rotate_file']) {
            $maxFiles = empty($config['max_files']) ? self::MAX_ROTATIONAL_FILES : $config['max_files'];
            $handler = new RotatingFileHandler($logFullPath, $maxFiles, $logLevel);
        } else {
            $handler = new StreamHandler($logFullPath, $logLevel);
        }

        // Line formatter will ensure all log entries are collapsed into a single line and with specific date formatting
        $handler->setFormatter(new LineFormatter(null, DATE_W3C, false, true));
        $monoLogger->pushHandler($handler);

        return $monoLogger;
    }

    public static function log($message, $type = self::INFO)
    {
        try {
            $source = self::SOURCE; 
            
            $data = [
                'source' => $source,
                'message' => $message,
                'type' => Logger::getLevelName($type),
                'logtime' => time()
            ]; 
            self::getMonoLogger($source.'.log')->log($type, $message); 
            
            self::logInApplicationLogger($data);   
            
        } catch (\Exception $exception) {
            self::logException($source, 'Error in save psc_logs', $exception);
        }
    }

    public static function logException($source, $message, \Exception $exception)
    {
        $message = $message.'---'.$exception->getMessage();
        self::log($message, Logger::ERROR);
    }
     
    public static function logInApplicationLogger($logDetails)
    {  
        $logger = \Pimcore\Log\ApplicationLogger::getInstance($logDetails['source'],true); 
        $logger->log($logDetails['type'], $logDetails['message']);
        
    }
}
