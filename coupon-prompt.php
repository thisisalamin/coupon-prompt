<?php

/**
 * Plugin Name: Coupon Prompt – Smart WooCommerce Coupon Notices
 * Description: Display smart coupon notices on cart/checkout if a valid WooCommerce coupon is available but not applied. Also optionally auto-applies best coupon based on cart total.
 * Plugin URI: https://wordpress.org/plugins/coupon-prompt/
 * Version: 1.0.1
 * Author: Crafely
 * Author URI: https://profiles.wordpress.org/crafely
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: coupon-prompt
 * Requires at least: 5.0
 * Tested up to: 6.8.2
 * Requires PHP: 7.2
 * Tags: woocommerce, coupons, cart, checkout, prompt
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Define constants for easier management and potential future use
if (!defined('COUPON_PROMPT_VERSION')) {
    define('COUPON_PROMPT_VERSION', '1.0.1');
}
if (!defined('COUPON_PROMPT_DIR')) {
    define('COUPON_PROMPT_DIR', plugin_dir_path(__FILE__));
}
if (!defined('COUPON_PROMPT_URL')) {
    define('COUPON_PROMPT_URL', plugin_dir_url(__FILE__));
}

// Include plugin classes
require_once COUPON_PROMPT_DIR . 'includes/class-coupon-prompt-admin.php';
require_once COUPON_PROMPT_DIR . 'includes/class-coupon-prompt-frontend.php';
require_once COUPON_PROMPT_DIR . 'includes/class-coupon-prompt-utils.php';

/**
 * Initialize the plugin, checking for WooCommerce.
 */
add_action('plugins_loaded', 'coupon_prompt_init');
function coupon_prompt_init()
{

    if (!class_exists('WooCommerce')) {
        // Show admin notice if WooCommerce is not active
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>' . esc_html__('Coupon Prompt requires WooCommerce to be installed and active.', 'coupon-prompt') . '</p></div>';
        });
        return;
    }

    // Initialize admin and frontend logic
    Coupon_Prompt_Admin::init();
    Coupon_Prompt_Frontend::init();
}
