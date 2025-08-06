=== Coupon Prompt – Smart WooCommerce Coupon Notices ===
Contributors: crafely, alaminit
Tags: woocommerce, coupons, prompt, discount, marketing
Requires at least: 5.0
Requires PHP: 7.2
Stable tag: 1.0.1
Tested up to: 6.8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


Smart WooCommerce coupon suggestions for cart and checkout—no auto-apply, just helpful, secure prompts.

== Description ==

🎉 **Boost sales and delight customers with smart, customizable WooCommerce coupon prompts!** 🎉

Coupon Prompt makes it easy to showcase your best deals—right where customers are most likely to use them. Show attractive, actionable coupon suggestions on the cart and checkout pages, only when a valid coupon is available and not yet applied. Customers instantly see which coupons they can use, how much they’ll save, and when the offer expires. With a single click, they can apply the coupon—no codes to remember, no confusion.

✨ **Why choose Coupon Prompt?**
- Increase coupon usage & conversions: Make discounts obvious and easy to use.
- Reduce friction: No more hunting for codes—customers see and apply coupons in one click.
- Full admin control: Customize the notice text, button label, and more for each coupon.
- Secure & user-friendly: Every “Apply Now” button is protected by a unique nonce.

🛠️ **Key Features**
- Per-coupon toggle: Choose which coupons are suggested with a simple checkbox in the coupon edit screen.
- Customizable notice & button text: Set your own message and button label for each coupon (admin option).
- Shows discount type and amount: Clearly displays “20% off”, “$5 off”, or “$5 off per item”.
- Optional expiry countdown: Show a live countdown (days, hours, minutes) until coupon expiry, or hide it per coupon.
- Preview before publish: Instantly see how your coupon prompt will look in the admin.
- Works for logged-in users and optionally for guests: By default, only logged-in users can apply coupons, but this can be changed with a filter.
- Permission checks: Only users with the correct permissions can apply coupons.
- Handles coupon usage limits and validity: Only valid, unused, and non-expired coupons are suggested.
- Fallback display: Coupon notices also appear in the main content area if WooCommerce hooks are not available.
- Translation-ready: All text is translatable and a .pot file is included.
- Compatible with most themes and WooCommerce setups.
- Lightweight & privacy-friendly: No bloat, no tracking, no auto-apply—just helpful prompts.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/coupon-prompt` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Edit or create coupons and enable the "Show in Cart/Checkout?" option as needed.
4. Optionally enable the "Show Expiry Countdown?" option per coupon.
5. (Optional) Customize the notice text and button label for each coupon in the coupon edit screen.

== Usage ==
1. **Enable Coupon Prompt for a Coupon:**
   - Go to **WooCommerce > Coupons** in your WordPress admin.
   - Edit an existing coupon or create a new one.
   - In the coupon edit screen, check the box labeled **"Show in Cart/Checkout?"** to make this coupon eligible for prompting.
   - (Optional) Check **"Show Expiry Countdown?"** to display a countdown timer for the coupon's expiry.
   - (Optional) Enter your custom notice text and button label for this coupon.

2. **How Customers See and Use Coupons:**
   - When a customer adds products to their cart and visits the cart or checkout page, eligible coupons will be displayed as notices.
   - Each notice shows the coupon code, your custom message, discount type/amount, and (if enabled) expiry countdown.
   - Customers can click the **"Apply Now"** (or your custom button text) to apply the coupon instantly and securely.

3. **Developer Options:**
   - By default, only logged-in users can apply coupons. To allow guests, use the `coupon_prompt_allow_guest_apply` filter in your theme or a custom plugin:
     ```
     add_filter( 'coupon_prompt_allow_guest_apply', '__return_true' );
     ```
   - All plugin text is translation-ready. Use the included `.pot` file for localization.

4. **Permissions:**
   - Only users with the correct WooCommerce permissions can apply coupons via the prompt.

5. **Fallback Display:**
   - If your theme does not support WooCommerce cart/checkout hooks, coupon notices will appear in the main content area.

== Frequently Asked Questions ==
= Can I customize the coupon notice or button text? =
Yes! When editing a coupon, you can set your own notice message and button label for each coupon prompt.

= Does this plugin auto-apply coupons? =
No, it only suggests eligible coupons. Customers must click to apply.

= Can I control which coupons are suggested? =
Yes, use the checkbox on the coupon edit page.

= Can I show/hide the expiry countdown? =
Yes, use the "Show Expiry Countdown?" option per coupon.

= Can guests apply coupons? =
By default, only logged-in users can apply coupons. Developers can enable guest coupon application using the `coupon_prompt_allow_guest_apply` filter.

= Is this plugin translation-ready? =
Yes, all text is translatable and a .pot file is included.

== Screenshots ==
1. Coupon prompt on the cart page
2. Coupon settings in the admin coupon edit screen

== Changelog ==
= 1.0.0 =
* Initial release: Smart coupon prompt, per-coupon toggle, expiry countdown, translation ready.

== Upgrade Notice ==
= 1.0.0 =
First public release.
