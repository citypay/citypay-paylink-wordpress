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
        
        //
        //  Some of the following test cases have been sourced from
        //  the Wikipedia article on valid / invalid email addresses.
        //  
        //  The link is provided below -
        //  
        //      https://en.wikipedia.org/wiki/Email_address
        //
        
        'xxx@xxx.yy' => true,
        'xxx@xxx.yy.zz' => true,
        'xxx@xxx.yy.zz.qq' => true,
        'xxx_yyy@xxx.zxy.ddf' => true,
        'xxx.yyy@ggg.gfd.as' => true,
        'prettyandsimple@example.com' => true,
        'very.common@example.com' => true,
        'disposable.style.email.with+symbol@example.com' => true,
        'other.email-with-dash@example.com' => true,
        
        //
        //  The following email addresses ought to be successfully parsed,
        //  recognized and matched but presently are not.
        //
       
        //'"much.more unusual"@example.com' => true,
        //'"very.unusual.@.unusual.com"@example.com' => true,
        //'"very.(),:;<>[]\".VERY.\"very@\\ \"very\".unusual"@strange.example.com' => true,
        //'admin@mailserver1' => true,
        //'#!$%&\'*+-/=?^_`{}|~@example.org' => true,
        //'"()<>[]:,;@\\\"!#$%&\'*+-/=?^_`{}| ~.a\"@example.org' => true
        
        //
        //  The following email addresses ought fail to be successfully parsed,
        //  recognized and matched but presently are.
        //
        
        //
        //  (an @ character must separate the local and domain parts)
        //
        'Abc.example.com' => false,
        
        //
        //  (only one @ is allowed outside quotation marks)
        //
        'A@b@c@example.com' => false,
        
        //
        //  (none of the special characters in this local part is allowed
        //  outside quotation marks)
        //
        'a\"b(c)d,e:f;g<h>i[j\k]l@example.com' => false,
        
        //
        //  (quoted strings must be dot separated or the only element
        //  making up the local-part)
        //
        'just"not"right@example.com' => false,
        
        //
        //  (spaces, quotes, and backslashes may only exist when within
        //  quoted strings and preceded by a backslash)
        //
        'this is"not\allowed@example.com' => false,
        
        //
        //  (even if escaped (preceded by a backslash), spaces, quotes,
        //  and backslashes must still be contained by quotes)
        //
        'this\ still\"not\\allowed@example.com' => false,
        
        //
        //  (double dot before @)
        //
        //'john..doe@example.com' => false,
        
        //
        //  (double dot after @)
        //
        //'john.doe@example..com' => false
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
