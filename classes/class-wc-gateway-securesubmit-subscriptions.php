<?php
/*
Plugin Name: WooCommerce SecureSubmit Gateway
Plugin URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
Description: Heartland Payment Systems gateway for WooCommerce.
Version: 1.2.2
Author: SecureSubmit
Author URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
*/

class WC_Gateway_SecureSubmit_Subscriptions extends WC_Gateway_SecureSubmit
{
    public function __construct()
    {
        parent::__construct();

        $this->supports = array(
                            'products',
                            'refunds',
                            'subscriptions',
                            'subscription_cancellation',
                            'subscription_reactivation',
                            'subscription_suspension',
                            'subscription_amount_changes',
                            'subscription_payment_method_change',
                            'subscription_date_changes',
                          );

        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init()
    {
        if (class_exists('WC_Subscriptions_Order')) {
            add_action('scheduled_subscription_payment_' . $this->id, array($this, 'scheduledSubscriptionPayment'), 10, 3);
            add_action('woocommerce_subscriptions_changed_failing_payment_method_' . $this->id, array($this, 'updateFailingPaymentMethod'), 10, 3);
            add_filter('woocommerce_subscriptions_renewal_order_meta_query', array($this, 'removeRenewalOrderMeta'), 10, 4);
            add_filter('woocommerce_my_subscriptions_recurring_payment_method', array($this, 'maybeRenderSubscriptionPaymentMethod'), 10, 3);
        }
    }

    public function process_payment($orderId)
    {
        if (class_exists('WC_Subscriptions_Order') && WC_Subscriptions_Order::order_contains_subscription($orderId)) {
            return $this->processSubscription($orderId);
        } else {
            return parent::process_payment($orderId);
        }
    }

    public function processSubscription($orderId)
    {
        global $woocommerce;

        $order = new WC_Order($orderId);
        $securesubmitToken = isset($_POST['securesubmit_token']) ? woocommerce_clean($_POST['securesubmit_token']) : '';
        $useStoredCard = false;

        // used for card saving:
        $last_four = isset($_POST['last_four']) ? woocommerce_clean($_POST['last_four']) : '';
        $exp_month = isset($_POST['exp_month']) ? woocommerce_clean($_POST['exp_month']) : '';
        $exp_year = isset($_POST['exp_year']) ? woocommerce_clean($_POST['exp_year']) : '';
        $card_type = isset($_POST['card_type']) ? woocommerce_clean($_POST['card_type']) : '';
        $saveCard = isset($_POST['save_card']) ? woocommerce_clean($_POST['save_card']) : '';

        try {
            if (empty($securesubmitToken)) {
                if (isset($_POST['secure_submit_card']) && $_POST['secure_submit_card'] === 'new') {
                    throw new Exception(__('Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'wc_securesubmit'));
                }
            }

            $hpstoken = new HpsTokenData();

            if (is_user_logged_in() && isset($_POST['secure_submit_card']) && $_POST['secure_submit_card'] !== 'new') {
                $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false);

                if (isset($cards[$_POST['secure_submit_card']]['token_value'])) {
                    $hpstoken->tokenValue = (string)$cards[(int)$_POST['secure_submit_card']]['token_value'];
                    $useStoredCard = true;
                } else {
                    throw new Exception(__('Invalid saved card.', 'wc_securesubmit'));
                }
            } else {
                $hpstoken->tokenValue = $securesubmitToken;
            }

            try {
                $saveCardToCustomer = true;
                $initialPayment = WC_Subscriptions_Order::get_total_initial_payment($order);
                $response = null;
                if ($initialPayment > 0) {
                    $response = $this->processSubscriptionPayment($order, $initialPayment, $hpstoken, $saveCardToCustomer);
                }

                if (isset($response) && is_wp_error($response)) {
                    throw new Exception($response->get_error_message());
                }

                if ($saveCardToCustomer || $useStoredCard) {
                    if (is_user_logged_in()) {
                        $tokenval = (string)$response->tokenData->tokenValue;
                        $saveToOrder = false;

                        if ($response->tokenData->responseCode == '0' && !$useStoredCard) {
                            add_user_meta(get_current_user_id(), '_secure_submit_card', array(
                                'last_four' => $last_four,
                                'exp_month' => $exp_month,
                                'exp_year' => $exp_year,
                                'token_value' => $tokenval,
                                'card_type' => $card_type,
                            ));
                            $saveToOrder = true;
                        }

                        if ($useStoredCard) {
                            $tokenval = $hpstoken->tokenValue;
                            $saveToOrder = true;
                        }

                        if ($saveToOrder) {
                            add_post_meta($order->id, '_securesubmit_card_token', $tokenval, true);
                        }
                    }
                }

                $order->payment_complete();
                $woocommerce->cart->empty_cart();
                WC_Subscriptions_Manager::activate_subscriptions_for_order($order);

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } catch (HpsException $e) {
                throw new Exception(__($e->getMessage(), 'wc_securesubmit'));
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

    public function scheduledSubscriptionPayment($amount, $order, $productId)
    {
        $result = $this->processSubscriptionPayment($order, $amount);

        if (is_wp_error($result)) {
            WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($order, $productId);
        } else {
            WC_Subscriptions_Manager::process_subscription_payments_on_order($order);
        }
    }

    public function processSubscriptionPayment($order, $amount, $tokenData = null, $requestMulti = false)
    {
        global $woocommerce;

        if (!is_object($order)) {
            $order = new WC_Order($order);
        }
        $tokenValue = get_post_meta($order->id, '_securesubmit_card_token', true);
        $token = new HpsTokenData();
        $token->tokenValue = $tokenValue;

        if (!isset($tokenValue) && $tokenData == null) {
            return new WP_Error('securesubmit_error', __('SecureSubmit payment token not found', 'hps-securesubmit'));
        }

        if ($tokenData != null) {
            $token = $tokenData;
        }

        try {
            $chargeService = $this->getCreditService();
            $hpsaddress = $this->getOrderAddress($order);
            $cardHolder = $this->getOrderCardHolder($order, $hpsaddress);

            $details = new HpsTransactionDetails();
            $details->invoiceNumber = $order->id;

            $response = $chargeService->charge(
                $amount,
                strtolower(get_woocommerce_currency()),
                $token,
                $cardHolder,
                $requestMulti,
                $details
            );

            $order->add_order_note(sprintf(__('SecureSubmit payment completed (Transaction ID: %s)', 'hps-securesubmit'), $response->transactionId));
            add_post_meta($order->id, '_transaction_id', $response->transactionId, true);

            return $response;
        } catch (Exception $e) {
            return new WP_Error('securesubmit_error', sprintf(__('SecureSubmit payment error: %s', 'hps-securesubmit'), $e->getMessage()));
        }
    }

    public function updateFailingPaymentMethod($old, $new, $subscriptionKey)
    {
        update_post_meta($old->id, '_securesubmit_card_token', get_post_meta($new->id, '_securesubmit_card_token', true));
    }

    public function removeRenewalOrderMeta($orderMetaQuery, $originalOrderId, $renewalOrderId, $newOrderRole)
    {
        if ('parent' == $newOrderRole) {
            $orderMetaQuery .= " AND `meta_key` <> '_securesubmit_card_token' ";
        }
        return $orderMetaQuery;
    }

    public function maybeRenderSubscriptionPaymentMethod($paymentMethodToDisplay, $subscriptionDetails, WC_Order $order)
    {
        if ($this->id !== $order->recurring_payment_method || !$order->customer_user) {
            return $paymentMethodToDisplay;
        }

        $userId = $order->customer_user;
        $token  = get_post_meta($order->id, '_securesubmit_card_token', true);
        $cards  = get_user_meta($userId, '_secure_submit_card', false);

        if ($cards) {
            $foundCard = false;
            foreach ($cards as $card) {
                if ($card['token_value'] === $token) {
                    $foundCard              = true;
                    $paymentMethodToDisplay = sprintf(__('Via %s card ending in %s', 'hps-securesubmit'), $card['card_type'], $card['last_four']);
                    break;
                }
            }
            if (!$foundCard) {
                $paymentMethodToDisplay = sprintf(__('Via %s card ending in %s', 'hps-securesubmit'), $card[0]['card_type'], $card[0]['last_four']);
            }
        }

        return $paymentMethodToDisplay;
    }
}
new WC_Gateway_SecureSubmit_Subscriptions();
