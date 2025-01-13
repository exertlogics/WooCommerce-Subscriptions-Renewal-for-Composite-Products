<?php
namespace WSRCP\Helpers;

class Logger {
    private static $instance = null;
    private $log_file;
    private const MAX_LOG_SIZE = 20000000; // 20MB in bytes
    private const BACKUP_DIR = 'log-archives';
    
    private function __construct() {
        $this->log_file = WSRCP_PLUGIN_PATH . 'wsrcp.log';
        $this->initializeLogFile();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeLogFile() {
        if (!file_exists($this->log_file)) {
            $file = fopen($this->log_file, 'w');
            fclose($file);
        }
        if (!is_writable($this->log_file)) {
            chmod($this->log_file, 0777);
        }
    }

    private function checkFileSize() {
        return file_exists($this->log_file) ? filesize($this->log_file) : 0;
    }

    private function shouldRotateLog() {
        return $this->checkFileSize() >= self::MAX_LOG_SIZE;
    }

    private function ensureBackupDirectory() {
        $backup_path = dirname($this->log_file) . '/' . self::BACKUP_DIR;
        if (!file_exists($backup_path)) {
            mkdir($backup_path, 0777, true);
        }
        return $backup_path;
    }

    private function rotateLog() {
        if (file_exists($this->log_file)) {
            $backup_path = $this->ensureBackupDirectory();
            $timestamp = date('Y-m-d-H-i-s');
            $backup_file = $backup_path . '/wsrcp-' . $timestamp . '.log';
            
            rename($this->log_file, $backup_file);
            
            // Create new log file
            $file = fopen($this->log_file, 'w');
            fwrite($file, "[" . date('Y-m-d H:i:s') . "] Log file rotated. Previous log archived to: " . basename($backup_file) . "\n");
            fclose($file);
        }
    }

    public function log($message, $level = 'INFO') {
        // Check and rotate if needed
        if ($this->shouldRotateLog()) {
            $this->rotateLog();
        }

        $datetime = date('Y-m-d H:i:s');
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($backtrace[1]) ? $backtrace[1] : $backtrace[0];
        
        $log_message = sprintf(
            "[%s] [%s] %s:%d - %s\n",
            $datetime,
            $level,
            basename($caller['file']),
            $caller['line'],
            $message
        );
        
        $file = fopen($this->log_file, 'a');
        fwrite($file, $log_message);
        fclose($file);
    }

    public function info($message) {
        $this->log($message, 'INFO');
    }

    public function error($message) {
        $this->log($message, 'ERROR');
    }

    public function debug($message) {
        $this->log($message, 'DEBUG');
    }

    public function warning($message) {
        $this->log($message, 'WARNING');
    }

    public function __clone() {
        trigger_error('Cloning is not allowed.', E_USER_ERROR);
    }

    public function __wakeup() {
        trigger_error('Unserializing is not allowed.', E_USER_ERROR);
    }
}