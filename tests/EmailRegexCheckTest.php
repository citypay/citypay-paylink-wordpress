<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require('paylink.php');

//define("CP_PAYLINK_EMAIL_REGEX", '/^[A-Za-z0-9_.+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]*)+$/');

class EmailRegexCheckTest extends PHPUnit_Framework_TestCase {
   
    private $testEmailAddresses = array(
        'xxx@xxx.yy' => true,
        'xxx@xxx.yy.zz' => true,
        'xxx@xxx.yy.zz.qq' => true
    );
    
    public function testDump()
    {
        var_dump($this->testEmailAddresses);
    }
    
    public function testEmailAddressRegex()
    {        
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
