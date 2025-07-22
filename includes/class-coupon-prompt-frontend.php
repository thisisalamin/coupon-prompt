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
            $message = sprintf(
                '<div style="text-align:center;">🎉 ' . __('You are eligible for the "%s" coupon!', 'coupon-prompt') . ' <a href="%s" class="button button-small" style="margin-left: 10px;">' . __('Apply Now', 'coupon-prompt') . '</a></div>',
                esc_html($code),
                esc_url(add_query_arg('apply_coupon_prompt', $code))
            );
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
            !isset($_GET['apply_coupon_prompt'])
        ) {
            return;
        }
        $coupon_code = sanitize_text_field(wp_unslash($_GET['apply_coupon_prompt']));
        if (!WC()->cart->has_discount($coupon_code)) {
            if (method_exists(WC()->cart, 'add_discount')) {
                $applied = WC()->cart->add_discount($coupon_code);
                if ($applied) {
                    wc_add_notice(sprintf(__('Coupon "%s" applied!', 'coupon-prompt'), $coupon_code), 'success');
                } else {
                    wc_add_notice(sprintf(__('Could not apply coupon "%s". It might be invalid or not applicable.', 'coupon-prompt'), $coupon_code), 'error');
                }
            }
            wp_redirect(remove_query_arg('apply_coupon_prompt'));
            exit;
        }
    }
}
