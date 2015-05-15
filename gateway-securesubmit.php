<?php
/*
Plugin Name: WooCommerce SecureSubmit Gateway
Plugin URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
Description: Heartland Payment Systems gateway for WooCommerce.
Version: 1.2.0
Author: SecureSubmit
Author URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
*/

class WooCommerceSecureSubmitGateway
{
    public function __construct()
    {
            add_action('plugins_loaded', array($this, 'init'), 0);
    }

    public function init()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        load_plugin_textdomain('wc_securesubmit', false, dirname(plugin_basename(__FILE__)) . '/languages');

        include_once('classes/class-wc-gateway-securesubmit.php');
        include_once('classes/class-wc-gateway-securesubmit-subscriptions.php');

        add_filter('woocommerce_payment_gateways', array($this, 'addGateway'));
        add_action('woocommerce_after_my_account', array($this, 'savedCards'));
    }

    public function addGateway($methods)
    {
        if (class_exists('WC_Subscriptions_Order')) {
            $methods[] = 'WC_Gateway_SecureSubmit_Subscriptions';
        } else {
            $methods[] = 'WC_Gateway_SecureSubmit';
        }
        return $methods;
    }

    public function savedCards()
    {
        $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false);

        if (!$cards) {
            return;
        }

        if (isset($_POST['delete_card']) && wp_verify_nonce($_POST['_wpnonce'], "secure_submit_del_card")) {
            $card = $cards[(int) $_POST['delete_card']];
            delete_user_meta(get_current_user_id(), '_secure_submit_card', $card);
        }

        if (!$cards) {
            return;
        }
        
        $path = plugin_dir_path(__FILE__);
        include $path . 'templates/saved-cards.php';
    }
}
new WooCommerceSecureSubmitGateway();
