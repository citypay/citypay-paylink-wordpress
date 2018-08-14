<?php

class CityPay_Logger
{
    public static function logFilePathName($plugin_file_path) {
        return dirname($plugin_file_path)
            .'/logs/'
            .date('Y-m-d').'.log';
    }
    
    private $log_file;
    private $newline;
    
    private function _getNewLineString($platform) {
        static $map = array(
                'Windows NT' => "\n\r"
            );
        
        if (array_key_exists($platform, $map)) {
            return $map[$platform];
        } else {
            return "\n";
        }
    }
    
    public function __construct($plugin_file_path) {
        $this->log_file = self::logFilePathName($plugin_file_path);
        $log_file_directory = dirname($this->log_file);
        if (!file_exists($log_file_directory)) {
            mkdir($log_file_directory, 0777, true);
        }
       
        $this->newline = self::_getNewLineString(php_uname('s'));
    }
    
    public function debugLog($message) {
        $_message = $this->newline.date(DATE_RFC2822).' - '.$message;
        error_log($_message, 3, $this->log_file);
    }
}