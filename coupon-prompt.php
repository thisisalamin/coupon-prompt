<?php

/**
 * Plugin Name: Coupon Prompt
 * Description: Display smart coupon notices on cart/checkout if a valid WooCommerce coupon is available but not applied. Also optionally auto-applies best coupon based on cart total.
 * Version: 1.0.0
 * Author: Crafely
 * Author URI: https://profiles.wordpress.org/crafely
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: coupon-prompt
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Define constants for easier management and potential future use
if (!defined('COUPON_PROMPT_VERSION')) {
    define('COUPON_PROMPT_VERSION', '1.0.0');
}
if (!defined('COUPON_PROMPT_DIR')) {
    define('COUPON_PROMPT_DIR', plugin_dir_path(__FILE__));
}
if (!defined('COUPON_PROMPT_URL')) {
    define('COUPON_PROMPT_URL', plugin_dir_url(__FILE__));
}

/**
 * Initialize the plugin, checking for WooCommerce.
 */
add_action('plugins_loaded', 'coupon_prompt_init');
function coupon_prompt_init()
{
    // Load text domain for translations
    load_plugin_textdomain('coupon-prompt', false, basename(COUPON_PROMPT_DIR) . '/languages');

    if (!class_exists('WooCommerce')) {
        // Show admin notice if WooCommerce is not active
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>' . esc_html__('Coupon Prompt requires WooCommerce to be installed and active.', 'coupon-prompt') . '</p></div>';
        });
        return;
    }

    // Show coupon prompt notice on cart/checkout (standard hooks)
    add_action('woocommerce_before_cart', 'coupon_prompt_show_coupon_notice');
    add_action('woocommerce_before_checkout_form', 'coupon_prompt_show_coupon_notice');

    // Fallback: Inject coupon prompt at top of cart/checkout content if hooks are missing
    add_filter('the_content', 'coupon_prompt_content_fallback', 1);

    // Handle manual apply link
    add_action('template_redirect', 'coupon_prompt_apply_coupon');
}

/**
 * Fallback: Inject coupon prompt at the top of cart/checkout content if standard hooks are missing.
 */
function coupon_prompt_content_fallback($content)
{
    if (!function_exists('is_cart') || !function_exists('is_checkout')) return $content;
    if (is_cart() || is_checkout()) {
        ob_start();
        coupon_prompt_show_coupon_notice();
        $notices = ob_get_clean();
        // Only prepend if there are notices (avoid double output)
        if (!empty($notices)) {
            return $notices . $content;
        }
    }
    return $content;
}

/**
 * Display coupon prompt notice on cart and checkout pages.
 */
function coupon_prompt_show_coupon_notice()
{
    // Check all WooCommerce dependencies before proceeding
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

    if (WC()->cart->is_empty()) {
        return;
    }

    if (!wc_coupons_enabled()) {
        return;
    }


    // Fetch all published coupons
    $args = array(
        'posts_per_page' => -1,
        'post_type'      => 'shop_coupon',
        'post_status'    => 'publish',
        'fields'         => 'ids', // Fetch only IDs for performance
    );
    $coupon_ids = get_posts($args); // Using get_posts is often simpler for just IDs

    $coupons = array();
    if (!empty($coupon_ids)) {
        foreach ($coupon_ids as $coupon_id) {
            // Only include coupons with the meta set to 'yes'
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

    if (empty($coupons)) {
        return;
    }

    $best_coupon_code = null;
    $highest_discount = 0;
    $notices_shown = false; // Renamed for clarity

    foreach ($coupons as $coupon) {
        $code = $coupon->get_code();

        // Skip if coupon is already applied
        if (WC()->cart->has_discount($code)) {
            continue;
        }

        // Check usage limit
        if ($coupon->get_usage_limit() && $coupon->get_usage_count() >= $coupon->get_usage_limit()) {
            continue;
        }

        // Check coupon validity against current cart
        // Using is_valid( $coupon_code ) or directly $coupon->is_valid() might be better for robust check
        // The is_valid() method on WC_Coupon object itself checks all conditions
        if (!$coupon->is_valid()) {
            continue;
        }

        // At this point, the coupon is valid and not applied

        $discount = coupon_prompt_estimate_discount($coupon);

        if ($discount > $highest_discount) {
            $highest_discount = $discount;
            $best_coupon_code = $code;
        }

        // Show notice for each eligible coupon
        if (function_exists('wc_print_notice')) {
            $message = sprintf(
                '<div style="text-align:center;">🎉 ' . __('You are eligible for the "%s" coupon!', 'coupon-prompt') . ' <a href="%s" class="button button-small" style="margin-left: 10px;">' . __('Apply Now', 'coupon-prompt') . '</a></div>',
                esc_html($code),
                esc_url(add_query_arg('apply_coupon_prompt', $code))
            );
            wc_print_notice($message, 'notice');
            $notices_shown = true;
        }
    }

    // No debug notices if no eligible coupons found

    // Auto-apply removed: coupons will only be applied when user clicks 'Apply Now'.
}

/**
 * Handle manual coupon application via URL parameter.
 */
function coupon_prompt_apply_coupon()
{
    if (
        !function_exists('WC') ||
        !WC()->cart ||
        !method_exists(WC()->cart, 'has_discount') ||
        !function_exists('wc_add_notice') ||
        !isset($_GET['apply_coupon_prompt'])
    ) {
        return; // Exit early if requirements not met or parameter not present
    }

    $coupon_code = sanitize_text_field(wp_unslash($_GET['apply_coupon_prompt'])); // Use wp_unslash for $_GET values
    error_log('Coupon Prompt: Attempting to apply coupon via URL: ' . $coupon_code); // Debug Log

    if (!WC()->cart->has_discount($coupon_code)) {
        if (method_exists(WC()->cart, 'add_discount')) {
            $applied = WC()->cart->add_discount($coupon_code);
            if ($applied) {
                wc_add_notice(sprintf(__('Coupon "%s" applied!', 'coupon-prompt'), $coupon_code), 'success');
                error_log('Coupon Prompt: Coupon "' . $coupon_code . '" successfully applied manually.'); // Debug Log
            } else {
                wc_add_notice(sprintf(__('Could not apply coupon "%s". It might be invalid or not applicable.', 'coupon-prompt'), $coupon_code), 'error');
                error_log('Coupon Prompt: Failed to apply coupon "' . $coupon_code . '" manually.'); // Debug Log
            }
        }
        // Redirect to remove the query arg, preventing re-application on refresh
        wp_redirect(remove_query_arg('apply_coupon_prompt'));
        exit;
        error_log('Coupon Prompt: Coupon "' . $coupon_code . '" already applied, no action needed.'); // Debug Log
        wp_redirect(remove_query_arg('apply_coupon_prompt')); // Still redirect to clean URL
        exit;
    }
}

/**
 * Estimate the discount amount for a given coupon.
 *
 * @param WC_Coupon $coupon The WooCommerce coupon object.
 * @return float The estimated discount amount.
 */
function coupon_prompt_estimate_discount($coupon)
{
    if (!function_exists('WC') || !WC()->cart) {
        return 0;
    }

    $discount = 0;
    $cart_total = WC()->cart->get_subtotal(); // get_subtotal() is generally preferred for calculating discounts based on cart contents before taxes/shipping.

    $discount_type = $coupon->get_discount_type();
    $coupon_amount = $coupon->get_amount();

    switch ($discount_type) {
        case 'percent':
            $discount = ($coupon_amount / 100) * $cart_total;
            break;
        case 'fixed_cart':
            $discount = $coupon_amount;
            break;
        case 'fixed_product':
            // For fixed_product, estimate based on total quantity of applicable items
            // This is a simplification; a true estimation would need to iterate through cart items
            // and apply the discount per-item based on coupon product/category restrictions.
            // For a 'best coupon' simple estimate, total cart quantity is a rough guess.
            $product_ids = $coupon->get_product_ids();
            $excluded_product_ids = $coupon->get_exclude_product_ids();
            $product_categories = $coupon->get_product_categories();
            $excluded_product_categories = $coupon->get_exclude_product_categories();

            $applicable_items_count = 0;
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product_id = $cart_item['product_id'];
                $product_qty = $cart_item['quantity'];
                $product_cat_ids = wc_get_product_term_ids($product_id, 'product_cat');

                // Check product exclusions
                if (!empty($excluded_product_ids) && in_array($product_id, $excluded_product_ids)) {
                    continue;
                }
                // Check category exclusions
                if (!empty($excluded_product_categories) && array_intersect($product_cat_ids, $excluded_product_categories)) {
                    continue;
                }

                $is_product_applicable = false;
                if (!empty($product_ids) && in_array($product_id, $product_ids)) {
                    $is_product_applicable = true;
                } elseif (!empty($product_categories) && array_intersect($product_cat_ids, $product_categories)) {
                    $is_product_applicable = true;
                } elseif (empty($product_ids) && empty($product_categories)) {
                    // If no product/category restrictions, it applies to all.
                    $is_product_applicable = true;
                }

                if ($is_product_applicable) {
                    $applicable_items_count += $product_qty;
                }
            }
            $discount = $coupon_amount * $applicable_items_count;
            break;
    }

    // Ensure discount does not exceed cart total for fixed and percentage discounts
    if ($discount > $cart_total && ($discount_type === 'percent' || $discount_type === 'fixed_cart')) {
        $discount = $cart_total;
    }

    return round($discount, 2); // Round to 2 decimal places
}


// Add custom field to coupon edit page
add_action('woocommerce_coupon_options', 'coupon_prompt_coupon_field');
function coupon_prompt_coupon_field($coupon_id)
{
    woocommerce_wp_checkbox(array(
        'id' => 'coupon_prompt_show',
        'label' => __('Show in Cart/Checkout?', 'coupon-prompt'),
        'description' => __('If checked, this coupon will be suggested to customers on the cart/checkout page.', 'coupon-prompt'),
        'desc_tip' => true,
        'value' => get_post_meta($coupon_id, 'coupon_prompt_show', true) ? 'yes' : '',
    ));
}

// Save custom field value
add_action('woocommerce_process_shop_coupon_meta', 'coupon_prompt_save_coupon_field', 10, 2);
function coupon_prompt_save_coupon_field($post_id, $coupon)
{
    $show = isset($_POST['coupon_prompt_show']) && $_POST['coupon_prompt_show'] === 'yes' ? 'yes' : '';
    update_post_meta($post_id, 'coupon_prompt_show', $show);
}
