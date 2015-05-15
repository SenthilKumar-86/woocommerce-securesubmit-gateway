<h3><?php _e('SecureSubmit', 'wc_securesubmit'); ?></h3>
<p><?php _e('SecureSubmit submits the credit card data directly to Heartland Payment Systems which responds with a token. That token is later charged.', 'wc_securesubmit'); ?></p>
<?php if (in_array(get_option('woocommerce_currency'), array('USD'))): ?>
    <table class="form-table">
        <?php $this->generate_settings_html(); ?>
    </table>
<?php else: ?>
    <div class="inline error">
        <p>
            <strong><?php _e('Gateway Disabled', 'wc_securesubmit'); ?></strong>
            <?php echo __('Choose US Dollars as your store currency to enable SecureSubmit.', 'wc_securesubmit'); ?>
        </p>
    </div>
<?php endif; ?>