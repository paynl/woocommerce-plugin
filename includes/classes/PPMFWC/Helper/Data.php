<?php

/**
 * PPMFWC_Gateways
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */
class PPMFWC_Helper_Data
{
    private static $paylog = null;
    private static $_payment_methods = null;
    private static $_options = [];

    /**
     * @param string $message
     * @param string|null $payTransactionId
     * @param array $infoFields
     * @param string $type
     * @phpcs:disable Squiz.Commenting.FunctionComment.MissingReturn     
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
     */
    public static function ppmfwc_payLogger($message, $payTransactionId = null, $infoFields = array(), $type = 'info')
    {
        if (self::$paylog === true || self::$paylog === null) {
            if (empty(self::$paylog)) {
                self::$paylog = get_option('paynl_paylogger') == 'yes';
                if (!self::$paylog) {
                    return;
                }
            }
            if (!in_array($type, array('emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'))) {
                $type = 'info';
            }
            $payTransactionId = empty($payTransactionId) ? '-' : $payTransactionId;
            $message = '[' . $payTransactionId . '] ' . ucfirst($message);
            if (!empty($infoFields) && is_array($infoFields)) {
                $message .= ' | ';
                foreach ($infoFields as $k => $v) {
                    $message .= $k . '=' . $v . ' | ';
                }
                $message = substr($message, 0, -2);
            }

            $logger = wc_get_logger();
            $logger->log(strtolower($type), $message, array('source' => 'pay-payments-for-woocommerce'));
        }
    }

    /**
     * Check for existing textfield and returns it sanitized
     *
     * @param string $fieldName
     * @param boolean $bForceString
     * @return false|string
     */
    public static function getPostTextField($fieldName, $bForceString = false)
    {
        $result = isset($_POST[$fieldName]) ? sanitize_text_field($_POST[$fieldName]) : false;
        if (empty($result) && $bForceString === true) {
            $result = '';
        }
        return $result;
    }

    /**
     * @param string $fieldName
     * @param boolean $bForceString
     * @return false|string
     */
    public static function getRequestArg($fieldName, $bForceString = false)
    {
        $exchange = $_REQUEST;
        if (wp_is_json_request()) {
            $jsonRequest = file_get_contents('php://input');
            $exchange = json_decode($jsonRequest, true);
        }

        $result = isset($exchange[$fieldName]) ? sanitize_text_field($exchange[$fieldName]) : false;
        if (empty($result) && $bForceString === true) {
            $result = '';
        }
        return $result;
    }

    /**
     * @return string
     */
    public static function getIp()
    {
        # Just get the headers if we can or else use the SERVER global
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = $_SERVER;
        }
        # Get the forwarded IP if it exists
        if (array_key_exists('X-Forwarded-For', $headers)) {
            $the_ip = $headers['X-Forwarded-For'];
        } elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $headers)) {
            $the_ip = $headers['HTTP_X_FORWARDED_FOR'];
        } else {
            $the_ip = $_SERVER['REMOTE_ADDR'];
        }
        $arrIp = explode(',', $the_ip);
        $the_ip = $arrIp[0];

        # Remove the portnumber if there is one (only ipv4)
        if (!filter_var(trim($the_ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($pos = strpos($the_ip, ':')) {
                $the_ip = substr($the_ip, 0, $pos);
            }
        }

        return filter_var(trim($the_ip), FILTER_VALIDATE_IP);
    }

    /**
     * @return boolean
     */
    public static function isTestMode()
    {
        if (get_option('paynl_test_mode') == 'yes') {
            return true;
        }
        $ip = self::getIp();
        $ipconfig = get_option('paynl_test_ipadress');
        if (!empty($ipconfig)) {
            $allowed_ips = explode(',', $ipconfig);
            if (
                in_array($ip, $allowed_ips) &&
                filter_var($ip, FILTER_VALIDATE_IP) &&
                strlen($ip) > 0 &&
                count($allowed_ips) > 0
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public static function loadPaymentMethods()
    {
        global $wpdb;

        $paymentOptions = self::getPaymentOptionsList();

        $table_name_options = $wpdb->prefix . "pay_options";
        $table_name_option_subs = $wpdb->prefix . "pay_option_subs";

        $wpdb->query('DELETE FROM `' . $table_name_option_subs . '`');
        $wpdb->query('DELETE FROM `' . $table_name_options . '`');

        foreach ($paymentOptions as $paymentOption) {
            $image = '';
            if (isset($paymentOption['brand']['id'])) {
                $image = PPMFWC_PLUGIN_URL . 'assets/logos/' . $paymentOption['brand']['id'] . '.png';
            } elseif (isset($paymentOption['id'])) {
                $image = 'https://static.pay.nl/payment_profiles/25x25/' . $paymentOption['id'] . '.png';
            }

            $sql = 'INSERT INTO `' . $table_name_options . '` (id,name,image,update_date) VALUES (%d,%s,%s,%s) ON DUPLICATE KEY UPDATE name = %s, image = %s, update_date = %s';
            $sql = $wpdb->prepare($sql, $paymentOption['id'], $paymentOption['visibleName'], $image, current_time('mysql'), $paymentOption['visibleName'], $image, current_time('mysql'));
            $wpdb->query($sql);

            if ($paymentOption['id'] == 10 && isset($paymentOption['banks'])) {
                foreach ($paymentOption['banks'] as $paymentOptionSub) {
                    $image = 'https://static.pay.nl/ideal/banks/' . $paymentOptionSub['id'] . '.png';
                    $sql = 'INSERT INTO `' . $table_name_option_subs . '` (option_id,option_sub_id,name,image,active) VALUES (%d,%s,%s,%s,%d) ON DUPLICATE KEY UPDATE name = %s, image = %s';
                    $sql = $wpdb->prepare($sql, $paymentOption['id'], $paymentOptionSub['id'], $paymentOptionSub['visibleName'], $image, true, $paymentOptionSub['visibleName'], $image);
                    $wpdb->query($sql);
                }
            }
        }
    }

    /**
     * @return array
     */
    public static function getOptions()
    {
        global $wpdb;

        $table_name_options = $wpdb->prefix . "pay_options";
        $query = "SELECT id, name, image, update_date FROM $table_name_options";

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * @param integer $optionId
     * @return array
     */
    public static function getOptionSubs($optionId)
    {
        global $wpdb;

        $table_name_option_subs = $wpdb->prefix . "pay_option_subs";
        $query = $wpdb->prepare("SELECT option_id, option_sub_id, name, image "
            . "FROM $table_name_option_subs "
            . "WHERE active = 1 AND option_id = %d", $optionId);

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * @param integer $optionId
     * @return boolean
     */
    public static function isOptionAvailable($optionId)
    {
        $options = self::getAllOptions();
        return isset($options[$optionId]);
    }

    /**
     * @return array
     */
    public static function getAllOptions()
    {
        global $wpdb;

        if (empty(self::$_options)) {
            $query = 'SELECT id, name, image, update_date FROM ' . $wpdb->prefix . 'pay_options';
            $result = $wpdb->get_results($query, ARRAY_A);
            $methods = array();
            foreach ($result as $paymentMethod) {
                $methods[$paymentMethod['id']] = $paymentMethod;
            }
            self::$_options = $methods;
        }

        return self::$_options;
    }

    /**
     * @return array
     */
    public static function getPaymentOptionsList()
    {
        if (empty(self::$_payment_methods)) {
            PPMFWC_Gateway_Abstract::loginSDK();
            $paymentOptions = \Paynl\Paymentmethods::getList();
            self::$_payment_methods = $paymentOptions;
        }

        return self::$_payment_methods;
    }

    /**
     * @return string
     */
    public static function getBrowserLanguage()
    {
        if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
            return self::parseDefaultLanguage($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
        } else {
            return self::parseDefaultLanguage(null);
        }
    }

    /**
     * @param string $http_accept
     * @param string $deflang
     * @return string
     */
    private static function parseDefaultLanguage($http_accept, $deflang = "en")
    {
        if (isset($http_accept) && strlen($http_accept) > 1) {
            # Split possible languages into array
            $x = explode(",", $http_accept);
            foreach ($x as $val) {
                #check for q-value and create associative array. No q-value means 1 by rule
                if (
                    preg_match(
                        "/(.*);q=([0-1]{0,1}.[0-9]{0,4})/i",
                        $val,
                        $matches
                    )
                ) {
                    $lang[$matches[1]] = (float)$matches[2] . '';
                } else {
                    $lang[$val] = 1.0;
                }
            }

            $arrAvailableLanguages = self::ppmfwc_getAvailableLanguages();
            $arrAvailableLanguages = array_keys($arrAvailableLanguages);

            array_pop($arrAvailableLanguages);

            # Return default language (highest q-value)
            $qval = 0.0;
            if (isset($lang) && is_array($lang)) {
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

    /**
     * @return string
     */
    public static function getVersion()
    {
        return '3.22.0';
    }

    /**
     * @return string
     */
    public static function getObject()
    {
        global $wp_version;
        global $woocommerce;
        $phpVersion = substr(phpversion(), 0, 3);
        $payVersion = self::getVersion();

        return substr('woocommerce ' . $payVersion . ' | ' . $wp_version . ' | ' . $phpVersion . ' | ' . $woocommerce->version, 0, 64);
    }

    /**
     * @return array
     */
    public static function ppmfwc_getAvailableLanguages()
    {
        return array(
            'nl' => 'Nederlands',
            'fr' => 'Francais',
            'en' => 'English',
            'us' => 'American',
            'de' => 'Deutsch',
            'dk' => 'Dansk',
            'es' => 'Español',
            'mx' => 'Mexicano',
            'hu' => 'Magyar',
            'it' => 'Italiano',
            'no' => 'Norsk',
            'pl' => 'Polski',
            'hr' => 'Hrvatski',
            'pt' => 'Português',
            'ro' => 'Română',
            'sv' => 'Svenska',
            'sl' => 'Slovenski',
            'tr' => 'Türk',
            'fi' => 'Suomalainen',
            'cz' => 'Česky',
            'gr' => 'Ελληνικά',
            'jp' => '日本語',
            'browser' => esc_html(__('Use browser language', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
        );
    }

    /**
     * @return string[]
     */
    public static function ppmfwc_getGateways()
    {
        $cores = \Paynl\Config::getCores();

        return array_merge($cores, ['custom' => esc_html(__('Custom', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))]);
    }
}
