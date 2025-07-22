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
        // Output nonce field for security
        wp_nonce_field('coupon_prompt_save_meta', 'coupon_prompt_meta_nonce');
        woocommerce_wp_checkbox(array(
            'id' => 'coupon_prompt_show',
            'label' => __('Show in Cart/Checkout?', 'coupon-prompt'),
            'description' => __('If checked, this coupon will be suggested to customers on the cart/checkout page.', 'coupon-prompt'),
            'desc_tip' => true,
            'value' => get_post_meta($coupon_id, 'coupon_prompt_show', true) ? 'yes' : '',
        ));
        echo '<div style="margin-top:8px;"></div>';
        woocommerce_wp_checkbox(array(
            'id' => 'coupon_prompt_show_expiry',
            'label' => __('Show Expiry Countdown?', 'coupon-prompt'),
            'description' => __('If checked, show the expiry countdown for this coupon in the prompt.', 'coupon-prompt'),
            'desc_tip' => true,
            'value' => get_post_meta($coupon_id, 'coupon_prompt_show_expiry', true) ? 'yes' : '',
        ));
        echo '<div style="margin-top:8px;"></div>';
        // Button Text
        woocommerce_wp_text_input(array(
            'id' => 'coupon_prompt_button_text',
            'label' => __('Button Text', 'coupon-prompt'),
            'description' => __('Text for the apply button.', 'coupon-prompt'),
            'desc_tip' => true,
            'value' => get_post_meta($coupon_id, 'coupon_prompt_button_text', true) ?: __('Apply Now', 'coupon-prompt'),
        ));
        // Message Text
        woocommerce_wp_text_input(array(
            'id' => 'coupon_prompt_message_text',
            'label' => __('Message Text', 'coupon-prompt'),
            'description' => __('Main message. Use {code}, {discount}, {expiry} as placeholders.', 'coupon-prompt'),
            'desc_tip' => true,
            'value' => get_post_meta($coupon_id, 'coupon_prompt_message_text', true) ?: __('🎉 You are eligible for the “{code}” coupon! {discount} {expiry}', 'coupon-prompt'),
        ));
        // Expiry Label
        woocommerce_wp_text_input(array(
            'id' => 'coupon_prompt_expiry_label',
            'label' => __('Expiry Label', 'coupon-prompt'),
            'description' => __('Label for expiry countdown. Use {days}, {hours}, {minutes}.', 'coupon-prompt'),
            'desc_tip' => true,
            'value' => get_post_meta($coupon_id, 'coupon_prompt_expiry_label', true) ?: __('Expires in {days} days', 'coupon-prompt'),
        ));
    }

    public static function save_coupon_field($post_id, $coupon)
    {
        // Verify nonce
        $nonce = isset($_POST['coupon_prompt_meta_nonce']) ? sanitize_text_field(wp_unslash($_POST['coupon_prompt_meta_nonce'])) : '';
        if (empty($nonce) || !wp_verify_nonce($nonce, 'coupon_prompt_save_meta')) {
            return;
        }
        $show = isset($_POST['coupon_prompt_show']) && $_POST['coupon_prompt_show'] === 'yes' ? 'yes' : '';
        update_post_meta($post_id, 'coupon_prompt_show', $show);
        $show_expiry = isset($_POST['coupon_prompt_show_expiry']) && $_POST['coupon_prompt_show_expiry'] === 'yes' ? 'yes' : '';
        update_post_meta($post_id, 'coupon_prompt_show_expiry', $show_expiry);
        // Save button text
        $button_text = isset($_POST['coupon_prompt_button_text']) ? sanitize_text_field(wp_unslash($_POST['coupon_prompt_button_text'])) : '';
        update_post_meta($post_id, 'coupon_prompt_button_text', $button_text);
        // Save message text
        $message_text = isset($_POST['coupon_prompt_message_text']) ? sanitize_text_field(wp_unslash($_POST['coupon_prompt_message_text'])) : '';
        update_post_meta($post_id, 'coupon_prompt_message_text', $message_text);
        // Save expiry label
        $expiry_label = isset($_POST['coupon_prompt_expiry_label']) ? sanitize_text_field(wp_unslash($_POST['coupon_prompt_expiry_label'])) : '';
        update_post_meta($post_id, 'coupon_prompt_expiry_label', $expiry_label);
    }
}
