<?php
// Frontend logic for Coupon Prompt plugin
if (!defined('ABSPATH')) exit;

class Coupon_Prompt_Frontend
{
    public static function init()
    {
        add_action('woocommerce_before_cart', [__CLASS__, 'show_coupon_notice']);
        add_action('woocommerce_before_checkout_form', [__CLASS__, 'show_coupon_notice']);
        add_filter('the_content', [__CLASS__, 'content_fallback'], 1);
        add_action('template_redirect', [__CLASS__, 'apply_coupon']);
    }

    public static function content_fallback($content)
    {
        if (!function_exists('is_cart') || !function_exists('is_checkout')) return $content;
        if (is_cart() || is_checkout()) {
            ob_start();
            self::show_coupon_notice();
            $notices = ob_get_clean();
            if (!empty($notices)) {
                return $notices . $content;
            }
        }
        return $content;
    }

    public static function show_coupon_notice()
    {
        if (
            !function_exists('WC') ||
            !class_exists('WC_Coupon') ||
            !function_exists('wc_print_notice') ||
            !function_exists('wc_add_notice') ||
            !function_exists('wc_coupons_enabled') ||
            !WC()->cart
        ) {
            return;
        }
        if (WC()->cart->is_empty()) return;
        if (!wc_coupons_enabled()) return;
        $args = array(
            'posts_per_page' => -1,
            'post_type'      => 'shop_coupon',
            'post_status'    => 'publish',
            'fields'         => 'ids',
        );
        $coupon_ids = get_posts($args);
        $coupons = array();
        if (!empty($coupon_ids)) {
            foreach ($coupon_ids as $coupon_id) {
                $show = get_post_meta($coupon_id, 'coupon_prompt_show', true);
                if ($show === 'yes') {
                    try {
                        $coupon = new WC_Coupon($coupon_id);
                        if ($coupon->get_id()) {
                            $coupons[] = $coupon;
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        }
        if (empty($coupons)) return;
        foreach ($coupons as $coupon) {
            $code = $coupon->get_code();
            if (WC()->cart->has_discount($code)) continue;
            if ($coupon->get_usage_limit() && $coupon->get_usage_count() >= $coupon->get_usage_limit()) continue;
            if (!$coupon->is_valid()) continue;

            // Discount type and amount
            $discount_type = $coupon->get_discount_type();
            $amount = $coupon->get_amount();
            $discount_label = '';
            if ($discount_type === 'percent') {
                /* translators: %d: percent discount value */
                $discount_label = sprintf(__('(%d%% off)', 'coupon-prompt'), (int) $amount);
            } elseif ($discount_type === 'fixed_cart') {
                /* translators: %s: formatted discount amount (currency) */
                $discount_label = sprintf(__('(%s off)', 'coupon-prompt'), wc_price($amount));
            } elseif ($discount_type === 'fixed_product') {
                /* translators: %s: formatted discount amount (currency) */
                $discount_label = sprintf(__('(%s off per item)', 'coupon-prompt'), wc_price($amount));
            }

            // Expiry countdown logic (only if enabled by admin)
            $expiry_html = '';
            $show_expiry = get_post_meta($coupon->get_id(), 'coupon_prompt_show_expiry', true);
            $expiry_label = get_post_meta($coupon->get_id(), 'coupon_prompt_expiry_label', true);
            if (!$expiry_label) {
                /* translators: {days} will be replaced with the number of days left */
                $expiry_label = __('Expires in {days} days', 'coupon-prompt');
            }
            if ($show_expiry === 'yes') {
                $expiry_timestamp = $coupon->get_date_expires() ? $coupon->get_date_expires()->getTimestamp() : false;
                if ($expiry_timestamp) {
                    $now = current_time('timestamp');
                    $seconds_left = $expiry_timestamp - $now;
                    if ($seconds_left > 0) {
                        $days = floor($seconds_left / 86400);
                        $hours = floor(($seconds_left % 86400) / 3600);
                        $minutes = floor(($seconds_left % 3600) / 60);
                        $label = $expiry_label;
                        $label = str_replace('{days}', $days, $label);
                        $label = str_replace('{hours}', $hours, $label);
                        $label = str_replace('{minutes}', $minutes, $label);
                        $expiry_html = '<span style="color:#d35400; font-size:90%; margin-left:10px;">' . esc_html($label) . '</span>';
                    } else {
                        $expiry_html = '<span style="color:#c0392b; font-size:90%; margin-left:10px;">' . __('Expired', 'coupon-prompt') . '</span>';
                    }
                }
            }

            // Add nonce to apply link
            $apply_url = add_query_arg(array(
                'apply_coupon_prompt' => $code,
                'coupon_prompt_nonce' => wp_create_nonce('coupon_prompt_apply_' . $code),
            ));

            // Get dynamic message and button text
            $message_text = get_post_meta($coupon->get_id(), 'coupon_prompt_message_text', true);
            if (!$message_text) {
                /* translators: {code} is the coupon code, {discount} is the discount label, {expiry} is the expiry string */
                $message_text = __('🎉 You are eligible for the “{code}” coupon! {discount} {expiry}', 'coupon-prompt');
            }
            $button_text = get_post_meta($coupon->get_id(), 'coupon_prompt_button_text', true);
            if (!$button_text) {
                /* translators: Button text for applying the coupon */
                $button_text = __('Apply Now', 'coupon-prompt');
            }
            // Replace placeholders
            // Color the discount label for theme visibility
            $discount_label_colored = '<span style="color:#2980b9; font-weight:bold;">' . $discount_label . '</span>';
            $message = str_replace(
                ['{code}', '{discount}', '{expiry}'],
                [esc_html($code), $discount_label_colored, $expiry_html],
                $message_text
            );
            $message = '<div style="text-align:center;">' . $message . ' <a href="' . esc_url($apply_url) . '" class="button button-small" style="margin-left: 10px;">' . esc_html($button_text) . '</a></div>';
            wc_print_notice($message, 'notice');
        }
    }

    public static function apply_coupon()
    {
        if (
            !function_exists('WC') ||
            !WC()->cart ||
            !method_exists(WC()->cart, 'has_discount') ||
            !function_exists('wc_add_notice') ||
            !isset($_GET['apply_coupon_prompt']) ||
            !isset($_GET['coupon_prompt_nonce'])
        ) {
            return;
        }
        $coupon_code = sanitize_text_field(wp_unslash($_GET['apply_coupon_prompt']));
        $nonce = sanitize_text_field(wp_unslash($_GET['coupon_prompt_nonce']));
        if (!wp_verify_nonce($nonce, 'coupon_prompt_apply_' . $coupon_code)) {
            wc_add_notice(__('Security check failed. Please try again.', 'coupon-prompt'), 'error');
            wp_redirect(remove_query_arg(array('apply_coupon_prompt', 'coupon_prompt_nonce')));
            exit;
        }
        if (!WC()->cart->has_discount($coupon_code)) {
            if (method_exists(WC()->cart, 'add_discount')) {
                $applied = WC()->cart->add_discount($coupon_code);
                if ($applied) {
                    /* translators: 1: coupon code */
                    wc_add_notice(sprintf(__('Coupon "%s" applied!', 'coupon-prompt'), $coupon_code), 'success');
                } else {
                    /* translators: 1: coupon code */
                    wc_add_notice(sprintf(__('Could not apply coupon "%s". It might be invalid or not applicable.', 'coupon-prompt'), $coupon_code), 'error');
                }
            }
            wp_redirect(remove_query_arg(array('apply_coupon_prompt', 'coupon_prompt_nonce')));
            exit;
        }
    }
}
