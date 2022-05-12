<?php

class CityPay_Filter {
    
    public static function cp_paylink_get_tag($s, &$i, &$i_max)
    {
        $j = $i++;

        $c = $s[$i];
        if ($c != "/") {
            $tag_type = CP_PAYLINK_OPENING_TAG;
        } else {
            $tag_type = CP_PAYLINK_CLOSING_TAG;
            $i++;
        }

        $tag = '';
        while ($i < $i_max) {
            $c = $s[$i++];
            if ($c == ">") {
                break;
            } else if ($c == " " || $c == "/r" || $c == "/n" || $c == "/t") { 
                break;
            } else {
                $tag .= $c;
                $i++;
            }
        }

        $attrs = array();
        if ($c == " " || $c == "/r" || $c == "/n" || $c == "/t")
        {
            while ($i < $i_max)
            {
                // purge whitespace
                while (++$i < $i_max) {
                    $c = $s[$i];
                    if ($c != " " && $c != "/r" && $c != "/n" && $c != "/t") {
                        break;
                    }
                }

                if ($c == "/" && $tag_type == CP_PAYLINK_OPENING_TAG) {
                    $tag_type = CP_PAYLINK_SELF_CLOSING_TAG;
                }

                $attr_name = '';
                $attr_value = null;
                while ($i < $i_max) {
                    $c = $s[$i];
                    if ($c == "=" || $c == " " || $c == "/r" || $c == "/n" || $c == "/t") {
                        break;
                    } else {
                        $attr_name .= $c;
                        $i++;
                    }
                }

                if ($c == "=") { 
                    $i++;
                    $attr_value = '';
                    while ($i < $i_max) {
                        $c = $s[$i];
                        if ($c == " " || $c == "/r" || $c == "/n" || $c == "/t") {
                            break;
                        } else {
                            $attr_value .= $c;
                            $i++;
                        }
                    }
                }

                $attrs[] = new attr($attr_name, $attr_value);
            }
        }

        if ($c == ">")
        {
            switch ($tag_type)
            {
            case CP_PAYLINK_OPENING_TAG:
                //$stack[] = new tag($tag, $)
                break;

            case CP_PAYLINK_SELF_CLOSING_TAG:
            case CP_PAYLINK_CLOSING_TAG:

                break;
            }
        }

        $tag_lc = strtolower($tag);
        $tag_obj = tag($tag_lc, $attrs, $j, $i, $tag_type);
    }

    public static function cp_paylink_trim_outer_p_and_br_tags($s) {

        $stack = array();
        $content = array();

        $i = 0;
        $i_max = strlen($s);
        while ($i < $i_max) {
            // purge whitespace
            while ($i < $i_max) {
                $c = $s[$i++];
                if ($c != " " && $c != "\n" && $c != "\r" && $c != "\t") { break; }
            }

            if ($i >= $i_max) { break; }

            if ($c == "<") {
                $stack[] = &$tag_obj;
                $content[] = &$tag_obj;
            } else {
                /*while ($i < $i_max) {
                    if 
                }*/
            }
        }
    }


    public static function cp_paylink_trim_p_and_br_tags($s) {
        $i = 0;
        $i_max = strlen($s);
        while ($i < $i_max) {
            $c = $s[$i];
            if ($c == "<") {
                $k = $i++;
                $tag = '';
                while ($i < $i_max) {
                    $c = $s[$i++];
                    if ($c != ">") {
                        $tag .= $c;
                    } else {
                        break;
                    }
                }
                $tag_lc = strtolower($tag);
                if ($tag_lc != "br" && $tag_lc != "br/" && $tag_lc != "br /") {
                    $i = $k;
                    break;
                }
            } else if ($c == " " || $c == "\n" || $c == "\r" || $c == "\t") {
                // do nothing
                $i++;
            } else {
                break;
            }
        }

        $j = $i_max - 1;
        while ($j > $i) {
            $c = $s[$j];
            if ($c == ">") {
                $k = $j + 1;
                $tag = "";
                while ($j > $i) {
                    $c = $s[$j--];
                    if ($c != "<") {
                        $tag .= $c;
                    } else {
                        break;
                    }
                }
                $tag_lcr = strrev(strtolower($tag));
                if ( $tag_lcr != "br" && $tag_lcr != "br/" && $tag_lcr != "br /") {
                    $j = $k;
                    break;
                }
            } else if ($c == " " || $c == "\n" || $c == "\r" || $c == "\t") {
                // do nothing
                $j--;
            } else {
                break;
            }
        }
        return substr($s, $i, (($j - $i) + 1));
    }
    
}

