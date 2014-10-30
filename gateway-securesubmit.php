<?php
/*
Plugin Name: WooCommerce SecureSubmit Gateway
Plugin URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
Description: Heartland Payment Systems gateway for WooCommerce.
Version: 1.0.5
Author: Mark Hagan
Author URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
*/
add_action( 'plugins_loaded', 'woocommerce_securesubmit_init', 0 );

function woocommerce_securesubmit_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) )
		return;

	load_plugin_textdomain( 'wc_securesubmit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	include_once('classes/class-wc-gateway-securesubmit.php');

	function add_securesubmit_gateway($methods) {
		$methods[] = 'WC_Gateway_SecureSubmit';
		return $methods;
	}

	function woocommerce_securesubmit_saved_cards() {
		$cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false);

		if (!cards)
			return;

		if (isset($_POST['delete_card']) && wp_verify_nonce($_POST['_wpnonce'], "secure_submit_del_card")) {
			$card = $cards[(int) $_POST['delete_card']];
			delete_user_meta(get_current_user_id(), '_secure_submit_card', $card);
		}

		$cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false);

		if (!cards)
		return;
		?>

		<h2>Saved Credit Cards</h2>
		<table>
			<thead>
				<tr>
					<th>Card Type</th>
					<th>Last Four</th>
					<th>Expiration</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($cards as $i => $card ) : ?>
				<tr>
					<td><?php esc_html_e($card['card_type']); ?></td>
					<td><?php esc_html_e($card['last_four']); ?></td>
					<td><?php esc_html_e($card['exp_month']); ?> / <?php esc_html_e($card['exp_year']); ?></td>
					<td>
						<form action="#saved-cards" method="POST">
	                        <?php wp_nonce_field ( 'secure_submit_del_card' ); ?>
	                        <input type="hidden" name="delete_card" value="<?php esc_attr($i); ?>">
	                        <input type="submit" value="Delete Card">
	                    </form>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php	
	}

	add_filter('woocommerce_payment_gateways', 'add_securesubmit_gateway');
	add_action('woocommerce_after_my_account', 'woocommerce_securesubmit_saved_cards');
}
