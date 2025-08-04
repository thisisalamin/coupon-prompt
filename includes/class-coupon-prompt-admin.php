<?php
/**
 * Admin UI for Coupon Prompt plugin.
 *
 * @package Coupon_Prompt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Coupon_Prompt_Admin
 */
class Coupon_Prompt_Admin {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_coupon_options', array( __CLASS__, 'coupon_field' ) );
		add_action( 'woocommerce_process_shop_coupon_meta', array( __CLASS__, 'save_coupon_field' ), 10, 2 );
	}

	/**
	 * Output coupon fields in admin.
	 *
	 * @param int $coupon_id Coupon ID.
	 */
	public static function coupon_field( $coupon_id ) {
		wp_nonce_field( 'coupon_prompt_save_meta', 'coupon_prompt_meta_nonce' );
		woocommerce_wp_checkbox(
			array(
				'id'          => 'coupon_prompt_show',
				'label'       => __( 'Show in Cart/Checkout?', 'coupon-prompt' ),
				'description' => __( 'If checked, this coupon will be suggested to customers on the cart/checkout page.', 'coupon-prompt' ),
				'desc_tip'    => true,
				'value'       => ( 'yes' === get_post_meta( $coupon_id, 'coupon_prompt_show', true ) ) ? 'yes' : '',
			)
		);
		echo '<div style="margin-top:8px;"></div>';
		woocommerce_wp_checkbox(
			array(
				'id'          => 'coupon_prompt_show_expiry',
				'label'       => __( 'Show Expiry Countdown?', 'coupon-prompt' ),
				'description' => __( 'If checked, show the expiry countdown for this coupon in the prompt.', 'coupon-prompt' ),
				'desc_tip'    => true,
				'value'       => ( 'yes' === get_post_meta( $coupon_id, 'coupon_prompt_show_expiry', true ) ) ? 'yes' : '',
			)
		);
	}

	/**
	 * Save coupon fields.
	 *
	 * @param int       $post_id Coupon post ID.
	 * @param WC_Coupon $coupon  Coupon object.
	 */
	public static function save_coupon_field( $post_id, $coupon ) {
		$nonce = isset( $_POST['coupon_prompt_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_prompt_meta_nonce'] ) ) : '';
		if ( empty( $nonce ) || false === wp_verify_nonce( $nonce, 'coupon_prompt_save_meta' ) ) {
			return;
		}
		$show = ( isset( $_POST['coupon_prompt_show'] ) && 'yes' === $_POST['coupon_prompt_show'] ) ? 'yes' : '';
		update_post_meta( $post_id, 'coupon_prompt_show', $show );
		$show_expiry = ( isset( $_POST['coupon_prompt_show_expiry'] ) && 'yes' === $_POST['coupon_prompt_show_expiry'] ) ? 'yes' : '';
		update_post_meta( $post_id, 'coupon_prompt_show_expiry', $show_expiry );
	}
}
