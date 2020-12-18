<?php

class PPMFWC_Helper_Data
{

    /**
     * @param $message
     * @param string $type
     */
    public static function ppmfwc_payLogger($message, $type = 'info')
    {
        if(!in_array($type, array('emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'))) {
            $type = 'info';
        }
        $logger = wc_get_logger();
        $logger->log(strtolower($type), $message, array('source' => 'pay-payments-for-woocommerce'));
    }

    /**
     * Check for existing textfield and returns it sanitized
     *
     * @param $fieldName
     * @return false|string
     */
    public static function getPostTextField($fieldName)
    {
        return isset($_POST[$fieldName]) ? sanitize_text_field($_POST[$fieldName]) : false;
    }

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
            $image = '';
            if (isset($paymentOption['brand']['id'])) {
                $image = PPMFWC_PLUGIN_URL . 'assets/logos/' . $paymentOption['brand']['id'] . '.png';
            } else if (isset($paymentOption['id'])) {
                $image = 'https://static.pay.nl/payment_profiles/25x25/' . $paymentOption['id'] . '.png';
            }
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

  public static function getAllOptions()
  {
    global $wpdb;

      $table_name_options = $wpdb->prefix . "pay_options";

      $query = $wpdb->prepare("SELECT id, name, image, update_date FROM $table_name_options");

      $result = $wpdb->get_results($query, ARRAY_A);

      $methods = array();
      foreach ($result as $paymentmethod) {
        $methods[$paymentmethod['id']] = $paymentmethod;
      }

      return $methods;
  }

    private static $_payment_methods = null;
    public static function getPaymentOptionsList()
    {
        if(empty(self::$_payment_methods)) {
          PPMFWC_Gateway_Abstract::loginSDK();
          $paymentOptions = \Paynl\Paymentmethods::getList();
          self::$_payment_methods = $paymentOptions;
        }

      return self::$_payment_methods;
    }

    public static function ppmfwc_getLogoSizes()
    {
        return array(
            'Auto' => 'Auto',
            0       => esc_html(__('Don\'t show logos', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
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
        if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
            return self::parseDefaultLanguage($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
        } else {
            return self::parseDefaultLanguage(NULL);
        }
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

            $arrAvailableLanguages = self::ppmfwc_getAvailableLanguages();
            $arrAvailableLanguages = array_keys($arrAvailableLanguages);

            //laatste er af halen (browsertaal)
            array_pop($arrAvailableLanguages);

            #return default language (highest q-value)
            $qval = 0.0;
            if(isset($lang) && is_array($lang)) {
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
        }
        return strtolower(substr($deflang, 0, 2));
    }

    public static function getObject() {

        global $wp_version;
        global $woocommerce;
        $ver = (float)phpversion();
        $payversion = PPMFWC_Gateway_Abstract::getVersion();

        $object = 'Woocommerce ' . $woocommerce->version . " | " . $payversion . " | " . $ver . " | " . $wp_version;

        return $object;

    }

    public static function ppmfwc_getAvailableLanguages()
    {
        return array(
            'nl' => esc_html(__('Dutch', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'en' => esc_html(__('English', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'de' => esc_html(__('German', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'es' => esc_html(__('Spanish', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'it' => esc_html(__('Italian', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'fr' => esc_html(__('French', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'browser' => esc_html(__('Use browser language', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
        );
    }

}
