<?php
/*
Plugin Name: WooCommerce SecureSubmit Gateway
Plugin URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
Description: Heartland Payment Systems gateway for WooCommerce.
Version: 1.2.0
Author: SecureSubmit
Author URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
*/
class WC_Gateway_SecureSubmit extends WC_Payment_Gateway
{
    public function __construct()
    {
        require_once 'includes/Hps.php';

        $this->id                   = 'securesubmit';
        $this->method_title         = __('SecureSubmit', 'wc_securesubmit');
        $this->icon                 = plugins_url('/assets/images/cards.png', dirname(__FILE__));
        $this->has_fields           = true;
        $this->initFormFields();
        $this->init_settings();
        $this->title                = $this->getSetting('title');
        $this->description          = $this->getSetting('description');
        $this->enabled              = $this->getSetting('enabled');
        $this->secret_key           = $this->getSetting('secret_key');
        $this->public_key           = $this->getSetting('public_key');
        $this->custom_error         = $this->getSetting('custom_error');
        $this->paymentaction        = $this->getSetting('paymentaction');
        $this->allow_card_saving    = ($this->getSetting('allow_card_saving') == 'yes' ? true : false);
        $this->supports             = array(
                                        'products',
                                        'refunds',
                                     );

        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        add_action('admin_notices', array($this, 'checks'));
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function checks()
    {
        global $woocommerce;

        if ($this->enabled == 'no') {
            return;
        }

        if (!$this->secret_key) {
            echo '<div class="error"><p>' . sprintf(__('SecureSubmit error: Please enter your secret key <a href="%s">here</a>', 'wc_securesubmit'), admin_url('admin.php?page=woocommerce&tab=payment_gateways&subtab=gateway-securesubmit')) . '</p></div>';
            return;
        } elseif (!$this->public_key) {
            echo '<div class="error"><p>' . sprintf(__('SecureSubmit error: Please enter your public key <a href="%s">here</a>', 'wc_securesubmit'), admin_url('admin.php?page=woocommerce&tab=payment_gateways&subtab=gateway-securesubmit')) . '</p></div>';
            return;
        }
    }

    public function is_available()
    {
        global $woocommerce;

        if ($this->enabled == "yes") {
            if ($woocommerce->version < '1.5.8') {
                return false;
            }

            // we will be adding more currencies in the near future, but today we are bound to USD
            if (!in_array(get_option('woocommerce_currency'), array('USD'))) {
                return false;
            }

            if (!$this->secret_key) {
                return false;
            }

            if (!$this->public_key) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function initFormFields()
    {
        $this->form_fields = array(
            'enabled' => array(
                            'title' => __('Enable/Disable', 'wc_securesubmit'),
                            'label' => __('Enable SecureSubmit', 'wc_securesubmit'),
                            'type' => 'checkbox',
                            'description' => '',
                            'default' => 'no'
                       ),
            'title' => array(
                            'title' => __('Title', 'wc_securesubmit'),
                            'type' => 'text',
                            'description' => __('This controls the title the user sees during checkout.', 'wc_securesubmit'),
                            'default' => __('Credit Card', 'wc_securesubmit')
                       ),
            'description' => array(
                            'title' => __('Description', 'wc_securesubmit'),
                            'type' => 'textarea',
                            'description' => __('This controls the description the user sees during checkout.', 'wc_securesubmit'),
                            'default' => 'Pay with your credit card via SecureSubmit.'
                       ),
            'public_key' => array(
                            'title' => __('Public Key', 'wc_securesubmit'),
                            'type' => 'text',
                            'description' => __('Get your API keys from your SecureSubmit account.', 'wc_securesubmit'),
                            'default' => ''
                       ),
            'secret_key' => array(
                            'title' => __('Secret Key', 'wc_securesubmit'),
                            'type' => 'text',
                            'description' => __('Get your API keys from your SecureSubmit account.', 'wc_securesubmit'),
                            'default' => ''
                       ),
            'custom_error' => array(
                            'title' => __('Custom Error', 'wc_securesubmit'),
                            'type' => 'textarea',
                            'description' => __('To use the default Secure Submit error message use %s in the custom message text, ex. My message. %s -> will be displayed as: My message. Original Secure Submit message.', 'wc_securesubmit'),
                            'default' => '%s'
                       ),
            'allow_card_saving' => array(
                            'title' => __('Allow Card Saving', 'wc_securesubmit'),
                            'label' => __('Allow Card Saving', 'wc_securesubmit'),
                            'type' => 'checkbox',
                            'description' => 'Note: to use the card saving feature, you must have multi-use tokenization enabled on your Heartland account.',
                            'default' => 'no'
                       ),
            'paymentaction' => array(
                    'title'       => __('Payment Action', 'wc_securesubmit'),
                    'type'        => 'select',
                    'description' => __('Choose whether you wish to capture funds immediately or authorize payment only.', 'wc_securesubmit'),
                    'default'     => 'sale',
                    'desc_tip'    => true,
                    'options'     => array(
                            'sale'          => __('Capture', 'wc_securesubmit'),
                            'authorization' => __('Authorize', 'wc_securesubmit')
                   )
           )
           );
    }

    public function admin_options()
    {
        $path = dirname(plugin_dir_path(__FILE__));
        include $path . '/templates/admin-options.php';
    }

    public function payment_fields()
    {
        $path = dirname(plugin_dir_path(__FILE__));
        include $path . '/templates/payment-fields.php';
    }

    public function payment_scripts()
    {
        if (!is_checkout()) {
            return;
        }

        // SecureSubmit tokenization library
        wp_enqueue_script('woocommerce_lib', plugins_url('assets/js/secure.submit-1.0.2.js', dirname(__FILE__)), array('jquery'), '1.0', true);
        // SecureSubmit js controller for WooCommerce
        wp_enqueue_script('woocommerce_securesubmit', plugins_url('assets/js/securesubmit.js', dirname(__FILE__)), array('jquery'), '1.0', true);

        $securesubmit_params = array(
            'key' => $this->public_key
        );

        wp_localize_script('woocommerce_securesubmit', 'wc_securesubmit_params', $securesubmit_params);
    }

    public function process_payment($order_id)
    {
        global $woocommerce;

        $order = new WC_Order($order_id);
        $securesubmit_token = isset($_POST['securesubmit_token']) ? woocommerce_clean($_POST['securesubmit_token']) : '';

        // used for card saving:
        $last_four = isset($_POST['last_four']) ? woocommerce_clean($_POST['last_four']) : '';
        $exp_month = isset($_POST['exp_month']) ? woocommerce_clean($_POST['exp_month']) : '';
        $exp_year = isset($_POST['exp_year']) ? woocommerce_clean($_POST['exp_year']) : '';
        $card_type = isset($_POST['card_type']) ? woocommerce_clean($_POST['card_type']) : '';

        try {
            $post_data = array();

            if (empty($securesubmit_token)) {
                if (isset($_POST['secure_submit_card']) && $_POST['secure_submit_card'] === 'new') {
                    throw new Exception(__('Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'wc_securesubmit'));
                }
            }

            $chargeService = $this->getCreditService();
            $hpsaddress = $this->getOrderAddress($order);
            $cardHolder = $this->getOrderCardHolder($order, $hpsaddress);

            $hpstoken = new HpsTokenData();

            if (is_user_logged_in() && isset($_POST['secure_submit_card']) && $_POST['secure_submit_card'] !== 'new') {
                $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false);

                if (isset($cards[$_POST['secure_submit_card']]['token_value'])) {
                    $hpstoken->tokenValue = $cards[$_POST['secure_submit_card']]['token_value'];
                } else {
                    throw new Exception(__('Invalid saved card.', 'wc_securesubmit'));
                }
            } else {
                $hpstoken->tokenValue = $securesubmit_token;
            }

            $details = new HpsTransactionDetails();
            $details->invoiceNumber = $order->id;

            try {
                if ($_POST['save_card'] === "true") {
                    $save_card_to_customer = true;
                } else {
                    $save_card_to_customer = false;
                }

                if ($this->paymentaction == 'sale') {
                    $response = $chargeService->charge(
                        $order->order_total,
                        strtolower(get_woocommerce_currency()),
                        $hpstoken,
                        $cardHolder,
                        $save_card_to_customer, // multi-use
                        $details
                    );
                } else {
                    $response = $chargeService->authorize(
                        $order->order_total,
                        strtolower(get_woocommerce_currency()),
                        $hpstoken,
                        $cardHolder,
                        $save_card_to_customer, // multi-use
                        $details
                    );
                }

                if ($save_card_to_customer) {
                    if (is_user_logged_in()) {
                        $tokenval = $response->tokenData->tokenValue;

                        if ($response->tokenData->responseCode == '0') {
                            add_user_meta(get_current_user_id(), '_secure_submit_card', array(
                                'last_four' => $last_four,
                                'exp_month' => $exp_month,
                                'exp_year' => $exp_year,
                                'token_value' => (string) $tokenval,
                                'card_type' => $card_type,
                            ));
                        }
                    }
                }

                $order->add_order_note(__('SecureSubmit payment completed', 'hps-securesubmit') . ' (Transaction ID: ' . $response->transactionId . ')');
                $order->payment_complete();
                $woocommerce->cart->empty_cart();

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } catch (HpsException $e) {
                $this->throwUserError(__($e->getMessage(), 'wc_securesubmit'));
            }
        } catch (Exception $e) {
            $error = __('Error:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else {
                $woocommerce->add_error($error);
            }
            return;
        }
    }

    public function process_refund($order_id, $amount = null, $reason = '')
    {
        global $woocommerce;
        $log = new WC_Logger();

        $order = wc_get_order($order_id);

        if (!$order) {
            return false;
        }

        $transactionId = null;
        $args = array(
            'post_id' => $order->id,
            'approve' => 'approve',
        );

        remove_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'));

        $comments = get_comments($args);
        foreach ($comments as $comment) {
            if (strpos($comment->comment_content, '(Transaction ID: ') !== false) {
                $explodedComment = explode(': ', $comment->comment_content);
                $transactionId = substr($explodedComment[1], 0, -1); // trim) from comment
            }
        }
        unset($comments);
        unset($comment);

        add_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'));

        if (!$transactionId) {
            return false;
        }

        try {
            $chargeService = $this->getCreditService();
            try {
                $response = $chargeService->refundTransaction(
                    $amount,
                    strtolower(get_woocommerce_currency()),
                    $transactionId
                );
                $order->add_order_note(__('SecureSubmit payment refunded', 'hps-securesubmit') . ' (Transaction ID: ' . $response->transactionId . ')');
                return true;
            } catch (HpsException $e) {
                $this->throwUserError(__($e->getMessage(), 'wc_securesubmit'));
            }
        } catch (Exception $e) {
            $error = __('Error:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else {
                $woocommerce->add_error($error);
            }
            return false;
        }
    }

    protected function throwUserError($error) {
        if ($customMessage = $this->custom_error) {
            $error = sprintf($customMessage, $error);
        }

        throw new Exception(__($error, 'wc_securesubmit'));
    }

    protected function getCreditService()
    {
        $config = new HpsServicesConfig();
        $config->secretApiKey = $this->secret_key;
        $config->versionNumber = '1510';
        $config->developerId = '002914';

        return new HpsCreditService($config);
    }

    protected function getOrderAddress($order)
    {
        $hpsaddress = new HpsAddress();
        $hpsaddress->address = $order->billing_address_1;
        $hpsaddress->city = $order->billing_city;
        $hpsaddress->state = $order->billing_state;
        $hpsaddress->zip = preg_replace('/[^a-zA-Z0-9]/', '', $order->billing_postcode);
        $hpsaddress->country = $order->billing_country;
        return $hpsaddress;
    }

    protected function getOrderCardHolder($order, $hpsaddress)
    {
        $cardHolder = new HpsCardHolder();
        $cardHolder->firstName = $order->billing_first_name;
        $cardHolder->lastName = $order->billing_last_name;
        $cardHolder->phone = preg_replace('/[^0-9]/', '', $order->billing_phone);
        $cardHolder->emailAddress = $order->billing_email;
        $cardHolder->address = $hpsaddress;
        return $cardHolder;
    }

    protected function getSetting($setting)
    {
        $value = null;
        if (isset($this->settings[$setting])) {
            $value = $this->settings[$setting];
        }
        return $value;
    }
}
