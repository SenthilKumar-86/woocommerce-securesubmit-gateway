<fieldset>
    <?php if ($this->description): ?>
        <p><?php echo $this->description; ?></p>
    <?php endif; ?>
    <?php if ($this->allow_card_saving): ?>
        <?php $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false); ?>
        <?php if (is_user_logged_in() && isset($cards)): ?>
            <p class="form-row form-row-wide">

                <a class="button" style="float:right;" href="<?php echo get_permalink(get_option('woocommerce_myaccount_page_id')); ?>#saved-cards"><?php _e('Saved Cards', 'wc_securesubmit'); ?></a>

                <?php foreach ($cards as $i => $card): ?>
                    <input type="radio" id="secure_submit_card_<?php echo $i; ?>" name="secure_submit_card" style="width:auto;" value="<?php echo $i; ?>" />
                    <label style="display:inline;" for="secure_submit_card_<?php echo $i; ?>"><?php echo $card['card_type']; ?> ending in <?php echo $card['last_four']; ?> (<?php echo $card['exp_month'] . '/' . $card['exp_year']; ?>)</label><br />
                <?php endforeach; ?>

                <input type="radio" id="new_card" name="secure_submit_card" style="width:auto;" <?php checked(1, 1); ?> value="new" /> <label style="display:inline;" for="new_card">Use a new card</label>
            </p>
            <div class="clear"></div>
        <?php endif; ?>
    <?php endif; ?>
    <div class="securesubmit_new_card">
        <p class="form-row form-row-wide">
            <label for="securesubmit_card_number"><?php _e("Credit Card number", 'wc_securesubmit') ?> <span class="required">*</span></label>
            <input type="text" autocomplete="off" class="input-text card-number" />
        </p>
        <div class="clear"></div>
        <p class="form-row form-row-first">
            <label for="cc-expire-month"><?php _e("Expiration date", 'wc_securesubmit') ?> <span class="required">*</span></label>
            <select id="cc-expire-month" class="woocommerce-select woocommerce-cc-month card-expiry-month">
                <option value=""><?php _e('Month', 'wc_securesubmit') ?></option>
                <?php
                $months = array();
                for ($i = 1; $i <= 12; $i++) {
                    $timestamp = mktime(0, 0, 0, $i, 1);
                    $num = date('n', $timestamp);
                    $name = date('F', $timestamp);
                    printf('<option value="%u">%s</option>', $num, $name);
                }
                ?>
            </select>
            <select id="cc-expire-year" class="woocommerce-select woocommerce-cc-year card-expiry-year">
                <option value=""><?php _e('Year', 'wc_securesubmit') ?></option>
                <?php
                for ($i = date('y'); $i <= date('y') + 15; $i++) {
                    printf('<option value="20%u">20%u</option>', $i, $i);
                }
                ?>
            </select>
        </p>
        <p class="form-row form-row-last">
            <label for="securesubmit_card_csc"><?php _e("Card security code", 'wc_securesubmit') ?> <span class="required">*</span></label>
            <input type="text" id="securesubmit_card_csc" maxlength="4" style="width:4em;" autocomplete="off" class="input-text card-cvc" />
            <span class="help securesubmit_card_csc_description"></span>
        </p>
        <?php if ($this->allow_card_saving == 'yes'): ?>
            <p class="form-row form-row-wide">
                <input type="checkbox" autocomplete="off" id="save_card" name="save_card" value="true" style="display:inline">
                <label for="save_card" style="display: inline;"><?php _e("Save Credit Card for Future Use", 'wc_securesubmit') ?></label>
            </p>
        <?php endif; ?>
        <div class="clear"></div>
    </div>
</fieldset>