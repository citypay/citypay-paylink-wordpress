<?php

/*
 * The following constant / macro definition is included to prevent
 * phpunit from quiting upon reaching the test defined, as follows, to
 * prevent an attacker from trying to invoke specific PHP scripts
 * independently of the core Wordpress application -
 * 
 *      defined('ABSPATH') or die;
 * 
 */

define('ABSPATH', true);

/*
 * The following Wordpress plugin -specific functions are provided to
 * enable the paylink.php file to be loaded: these functions are called
 * outside the context of class definitions and therefore execute
 * immediately upon loading of the relevant file.
 * 
 */

function __($s) { return $s; }
function add_action($a, $b) { }
function add_filter($a, $b) { }

require('./wp-content/plugins/citypay-paylink-wordpress/paylink.php');

class EmailRegexCheckTest extends PHPUnit_Framework_TestCase {
   
    private $testEmailAddresses = array(
        'xxx@xxx.yy' => true,
        'xxx@xxx.yy.zz' => true,
        'xxx@xxx.yy.zz.qq' => true
    );
    
    public function testEmailAddressRegex()
    {        
        echo "\n";
        foreach ($this->testEmailAddresses as $emailAddress => $result) {
            echo "Testing: ${emailAddress}\n";
            $r = preg_match(CP_PAYLINK_EMAIL_REGEX, $emailAddress);
            if ($result) {
                $this->assertTrue($r === 1);
            } else {
                $this->assertTrue($r === 0 || $r === FALSE);
            }
        }
        
        return;
    }
}
