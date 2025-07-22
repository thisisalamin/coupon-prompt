<?php
// Admin UI for Coupon Prompt plugin
if (!defined('ABSPATH')) exit;

class Coupon_Prompt_Admin
{
    public static function init()
    {
        add_action('woocommerce_coupon_options', [__CLASS__, 'coupon_field']);
        add_action('woocommerce_process_shop_coupon_meta', [__CLASS__, 'save_coupon_field'], 10, 2);
    }

    public static function coupon_field($coupon_id)
    {
        woocommerce_wp_checkbox(array(
            'id' => 'coupon_prompt_show',
            'label' => __('Show in Cart/Checkout?', 'coupon-prompt'),
            'description' => __('If checked, this coupon will be suggested to customers on the cart/checkout page.', 'coupon-prompt'),
            'desc_tip' => true,
            'value' => get_post_meta($coupon_id, 'coupon_prompt_show', true) ? 'yes' : '',
        ));
    }

    public static function save_coupon_field($post_id, $coupon)
    {
        $show = isset($_POST['coupon_prompt_show']) && $_POST['coupon_prompt_show'] === 'yes' ? 'yes' : '';
        update_post_meta($post_id, 'coupon_prompt_show', $show);
    }
}
