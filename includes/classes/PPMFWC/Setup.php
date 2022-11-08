<?php

class PPMFWC_Setup
{

    const db_version = 1.2;

    public static function ppmfwc_installBlog()
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $pay_db_version = get_option("pay_db_version");

        if ($pay_db_version != self::db_version) {
            self::checkRequirements();

            $table_name_transactions = $wpdb->prefix . "pay_transactions";
            $table_name_options = $wpdb->prefix . "pay_options";
            $table_name_option_subs = $wpdb->prefix . "pay_option_subs";
            $table_name_processing = $wpdb->prefix . "pay_processing";

            if ($pay_db_version < 1.1) {
                $sql = "ALTER TABLE `$table_name_transactions` MODIFY order_id BIGINT(20);";
                $wpdb->query($sql);
            }

            $sqlTransactions = "CREATE TABLE `$table_name_transactions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `transaction_id` varchar(50) NOT NULL,
            `option_id` int(11) NOT NULL,
            `option_sub_id` int(11) DEFAULT NULL,
            `amount` int(11) NOT NULL,
            `order_id` bigint(20) NOT NULL,
            `status` varchar(10) NOT NULL DEFAULT 'PENDING',
            `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `last_update` datetime DEFAULT NULL,
            `start_data` text NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `{$table_name_transactions}_transaction_id` (`transaction_id`)
        );";
            maybe_create_table($table_name_transactions, $sqlTransactions);

            $sqlOptions = "CREATE TABLE `$table_name_options` (
            `id` int(10) unsigned NOT NULL COMMENT 'Payment option Id',
            `name` varchar(255) NOT NULL COMMENT 'Payment option name',
            `image` varchar(255) NOT NULL COMMENT 'The url to the icon image',
            `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The datetime this payment option was refreshed',
            PRIMARY KEY (`id`)
        );";
            maybe_create_table($table_name_options, $sqlOptions);

            $sqlOptionSub = "CREATE TABLE `$table_name_option_subs` (
            `option_id` int(10) unsigned NOT NULL COMMENT 'Payment option Id',
            `option_sub_id` int(10) unsigned NOT NULL COMMENT 'Payment option sub Id',  
            `name` varchar(255) NOT NULL COMMENT 'The name of the option sub',
            `image` varchar(255) NOT NULL COMMENT 'The url to the icon image',
            `active` tinyint(1) NOT NULL COMMENT 'OptionSub  active or not',
            PRIMARY KEY (`option_id`, option_sub_id)
        );";
            maybe_create_table($table_name_option_subs, $sqlOptionSub);

            $sqlProcessing = "CREATE TABLE `$table_name_processing` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `transaction_id` varchar(50) NOT NULL,
            `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `{$table_name_processing}_transaction_id` (`transaction_id`)
        );";
            maybe_create_table($table_name_processing, $sqlProcessing);

            if (empty(get_option('paynl_order_description_prefix'))) {
                update_option('paynl_order_description_prefix', 'Order:');
            }

            update_option("pay_db_version", self::db_version);
        }
    }

    public static function ppmfwc_install()
    {
        if (is_multisite() && is_plugin_active('woocommerce-paynl-payment-methods/woocommerce-payment-paynl.php')) {
            global $wpdb, $blog_id;
            $dbquery = 'SELECT blog_id FROM ' . $wpdb->blogs;
            $ids = $wpdb->get_col($dbquery);
            foreach ($ids as $id) {
                switch_to_blog($id);
                self::ppmfwc_installBlog();
            }
            switch_to_blog($blog_id);
        } else {
            self::ppmfwc_installBlog();
        }
    }


    public static function ppmfwc_install_init()
    {
        self::ppmfwc_installBlog();
    }

    /**
     * @param $blog_id
     * @param null $user_id
     * @param null $domain
     * @param null $path
     * @param null $site_id
     * @param null $meta
     */
    public static function ppmfwc_newBlog($blog_id, $user_id = null, $domain = null, $path = null, $site_id = null, $meta = null)
    {
        global $wpdb;

        $old_blog = $wpdb->blogid;
        switch_to_blog($blog_id->blog_id);
        self::ppmfwc_installBlog();
        switch_to_blog($old_blog);
    }

    /**
     * Delete PAY. tables when a (multi)site gets deleted
     * @param $tables
     * @return mixed
     */
    public static function ppmfwc_delBlog($tables)
    {
        global $wpdb;
        $tables[] = $wpdb->prefix . 'pay_transactions';
        $tables[] = $wpdb->prefix . 'pay_options';
        $tables[] = $wpdb->prefix . 'pay_option_subs';
        $tables[] = $wpdb->prefix . 'pay_processing';

        update_option("pay_db_version", 0);

        return $tables;
    }

    private static function checkRequirements()
    {
        if (!is_plugin_active('woocommerce/woocommerce.php') && !is_plugin_active_for_network('woocommerce/woocommerce.php')) {
            $error = __('Cannot activate PAY. Payment Methods for WooCommerce because WooCommerce could not be found. Please install and activate WooCommerce first', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
            $title = __('Woocommerce not found', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);

            wp_die(esc_html($error), esc_html($title), array('back_link' => true));
        }
    }

    public static function ppmfwc_testConnection()
    {
        # Only run this if the setting is not saved
        if (get_option('paynl_verify_peer') === false) {
            try {
                # Test the connection using a dummy IP
                \Paynl\Validate::isPayServerIp('10.20.30.40');
                add_option('paynl_verify_peer', 'yes');
            } catch (Exception $e) {
                add_option('paynl_verify_peer', 'no');
                return false;
            }
        }
    }
}