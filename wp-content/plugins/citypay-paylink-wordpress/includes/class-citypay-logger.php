<?php

class CityPay_Logger
{
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
        $this->log_file = $plugin_file_path.'/logs/'.date('Y-m-d').'.log';
        $this->newline = self::_getNewLineString(php_uname('s'));
    }
    
    public function debugLog($message) {
        $_message = $this->newline.date(DATE_RFC2822).' - '.$message;
        error_log($_message, 3, $this->log_file);
    }
}
