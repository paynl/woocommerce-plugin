<?php

class Pay_Helper_Data
{

    public static function getIp()
    {

        //Just get the headers if we can or else use the SERVER global
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = $_SERVER;
        }
        //Get the forwarded IP if it exists
        if (array_key_exists('X-Forwarded-For', $headers)) {
            $the_ip = $headers['X-Forwarded-For'];
        } elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $headers)) {
            $the_ip = $headers['HTTP_X_FORWARDED_FOR'];
        } else {
            $the_ip = $_SERVER['REMOTE_ADDR'];
        }
        $arrIp = explode(',', $the_ip);
        $the_ip = $arrIp[0];

        // Remove the portnumber if there is one (only ipv4)
        if (!filter_var(trim($the_ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($pos = strpos($the_ip, ':')) {
                $the_ip = substr($the_ip, 0, $pos);
            }
        }

        $the_ip = filter_var(trim($the_ip), FILTER_VALIDATE_IP);

        return $the_ip;
    }

    public static function loadPaymentMethods()
    {
        global $wpdb;        

        $paymentOptions = self::getPaymentOptionsList();
        
        $table_name_options = $wpdb->prefix . "pay_options";
        $table_name_option_subs = $wpdb->prefix . "pay_option_subs";

        //eerst flushen
        $wpdb->query('TRUNCATE TABLE ' . $table_name_option_subs);
        $wpdb->query('TRUNCATE TABLE ' . $table_name_options);
          
        foreach ($paymentOptions as $paymentOption) {
            $image = plugins_url('/woocommerce-paynl-payment-methods/assets/logos/' . $paymentOption['brand']['id'] . '.png');
            $wpdb->insert(
                $table_name_options, array(
                'id' => $paymentOption['id'],
                'name' => $paymentOption['visibleName'],
                'image' => $image,
                'update_date' => current_time('mysql'),
            ), array('%d', '%s', '%s', '%s')
            );
            if ($paymentOption['id'] == 10 && isset($paymentOption['banks'])) {
                foreach ($paymentOption['banks'] as $paymentOptionSub) {
                    $image = 'https://static.pay.nl/ideal/banks/' . $paymentOptionSub['id'] . '.png';
                    $wpdb->insert(
                        $table_name_option_subs, array(
                        'option_id' => $paymentOption['id'],
                        'option_sub_id' => $paymentOptionSub['id'],
                        'name' => $paymentOptionSub['visibleName'],
                        'image' => $image,
                        'active' => true,
                    ), array('%d', '%d', '%s', '%s', '%d')
                    );
                }
            }
        }
    }
    
    
    public static function getOptions()
    {
        global $wpdb;

        $table_name_options = $wpdb->prefix . "pay_options";
        $query = "SELECT id, name, image, update_date FROM $table_name_options";

        $options = $wpdb->get_results($query, ARRAY_A);

        return $options;
    }

    public static function getOptionSubs($optionId)
    {
        global $wpdb;

        $table_name_option_subs = $wpdb->prefix . "pay_option_subs";
        $query = $wpdb->prepare("SELECT option_id, option_sub_id, name, image "
            . "FROM $table_name_option_subs "
            . "WHERE active = 1 AND option_id = %d", $optionId
        );

        $optionSubs = $wpdb->get_results($query, ARRAY_A);

        return $optionSubs;
    }

    public static function isOptionAvailable($optionId)
    {
        global $wpdb;

        $table_name_options = $wpdb->prefix . "pay_options";
        $query = $wpdb->prepare("SELECT id, name, image, update_date FROM $table_name_options WHERE id = %d", $optionId);

        $result = $wpdb->get_results($query, ARRAY_A);

        if (empty($result))
            return false;
        else
            return true;
    }

    public static function getPaymentOptionsList()
    {       
        Pay_Gateway_Abstract::loginSDK();

        $paymentOptions = \Paynl\Paymentmethods::getList();

        return $paymentOptions;
    }

    public static function getLogoSizes()
    {
        return array(
            0 => __('Don\'t show logos', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            '50x32' => '50x32',
            '40x26' => '40x26',
            '20x20' => '20x20',
            '25x25' => '25x25',
            '50x50' => '50x50',
            '75x75' => '75x75',
            '100x100' => '100x100'
        );
    }

    public static function getBrowserLanguage()
    {
        if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
            return self::parseDefaultLanguage($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
        else return self::parseDefaultLanguage(NULL);
    }

    private static function parseDefaultLanguage($http_accept, $deflang = "en")
    {
        if (isset($http_accept) && strlen($http_accept) > 1) {
            # Split possible languages into array
            $x = explode(",", $http_accept);
            foreach ($x as $val) {
                #check for q-value and create associative array. No q-value means 1 by rule
                if (preg_match("/(.*);q=([0-1]{0,1}.[0-9]{0,4})/i", $val,
                    $matches)) {
                    $lang[$matches[1]] = (float)$matches[2] . '';
                } else {
                    $lang[$val] = 1.0;
                }
            }

            $arrAvailableLanguages = self::getAvailableLanguages();
            $arrAvailableLanguages = array_keys($arrAvailableLanguages);

            //laatste er af halen (browsertaal)
            array_pop($arrAvailableLanguages);

            #return default language (highest q-value)
            $qval = 0.0;
            foreach ($lang as $key => $value) {
                $languagecode = strtolower(substr($key, 0, 2));

                if (in_array($languagecode, $arrAvailableLanguages)) {
                    if ($value > $qval) {
                        $qval = (float)$value;
                        $deflang = $key;
                    }
                }
            }
        }
        return strtolower(substr($deflang, 0, 2));
    }

    public static function getAvailableLanguages()
    {
        return array(
            'nl' => __('Dutch', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'en' => __('English', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'de' => __('German', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'es' => __('Spanish', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'it' => __('Italian', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'fr' => __('French', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'browser' => __('Use browser language', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
        );
    }

}
