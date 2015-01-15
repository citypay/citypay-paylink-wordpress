<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class CityPay_Library {
    public static function extractKeyValuesFromArray($array, $keys) {
        if (!is_array($array) && !is_array($keys)) {
            return;
        }

        $a = array();
        foreach ($keys as $key) {
            if (key_exists($key, $array)) {
                $a[$key] = $array[$key];
            }
        }

        return $a;
    }
}
