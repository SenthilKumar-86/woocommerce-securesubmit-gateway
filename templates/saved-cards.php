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
        <?php $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false); ?>
        <?php foreach ($cards as $i => $card): ?>
        <tr>
            <td><?php esc_html_e($card['card_type']); ?></td>
            <td><?php esc_html_e($card['last_four']); ?></td>
            <td><?php esc_html_e($card['exp_month']); ?> / <?php esc_html_e($card['exp_year']); ?></td>
            <td>
                <form action="#saved-cards" method="POST">
                    <?php wp_nonce_field('secure_submit_del_card'); ?>
                    <input type="hidden" name="delete_card" value="<?php esc_attr($i); ?>">
                    <input type="submit" value="Delete Card">
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>