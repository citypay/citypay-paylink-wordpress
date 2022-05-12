<?php
/**
 * A collection of input validation methods.
 *
 * @author rwbisson
 */
class CityPay_Validation {
    
    const CP_PAYLINK_EMAIL_REGEX = '/^[A-Za-z0-9_.+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]*)+$/';

    public static function validateEmailAddress($email_address) {
        return preg_match(self::CP_PAYLINK_EMAIL_REGEX, $email_address);
    }
    
    public static function validateMerchantId($merchant_id) {
        // TODO: implement proper validation test
        return 1;
    }
    
    public static function validateLicenceKey($licence_key) {
        // TODO: implement proper validation test
        return 1;
    }

    public static function validatePostbackUrl($postback) {
        // TODO: implement proper validation test
        return 1;
    }

    public static function validateCheckboxValue($value) {
        // TODO: implement proper validation test
        return 1;
    }
}
