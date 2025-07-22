<?php
// Utility functions for Coupon Prompt plugin
if (!defined('ABSPATH')) exit;

class Coupon_Prompt_Utils
{
    public static function estimate_discount($coupon)
    {
        if (!function_exists('WC') || !WC()->cart) return 0;
        $discount = 0;
        $cart_total = WC()->cart->get_subtotal();
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
                $product_ids = $coupon->get_product_ids();
                $excluded_product_ids = $coupon->get_exclude_product_ids();
                $product_categories = $coupon->get_product_categories();
                $excluded_product_categories = $coupon->get_exclude_product_categories();
                $applicable_items_count = 0;
                foreach (WC()->cart->get_cart() as $cart_item) {
                    $product_id = $cart_item['product_id'];
                    $product_qty = $cart_item['quantity'];
                    $product_cat_ids = wc_get_product_term_ids($product_id, 'product_cat');
                    if (!empty($excluded_product_ids) && in_array($product_id, $excluded_product_ids)) continue;
                    if (!empty($excluded_product_categories) && array_intersect($product_cat_ids, $excluded_product_categories)) continue;
                    $is_product_applicable = false;
                    if (!empty($product_ids) && in_array($product_id, $product_ids)) {
                        $is_product_applicable = true;
                    } elseif (!empty($product_categories) && array_intersect($product_cat_ids, $product_categories)) {
                        $is_product_applicable = true;
                    } elseif (empty($product_ids) && empty($product_categories)) {
                        $is_product_applicable = true;
                    }
                    if ($is_product_applicable) {
                        $applicable_items_count += $product_qty;
                    }
                }
                $discount = $coupon_amount * $applicable_items_count;
                break;
        }
        if ($discount > $cart_total && ($discount_type === 'percent' || $discount_type === 'fixed_cart')) {
            $discount = $cart_total;
        }
        return round($discount, 2);
    }
}
