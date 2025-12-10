<?php
/**
 * Plugin Name: CHIP for FluentCart
 * Plugin URI: https://www.chip-in.asia
 * Description: Integrate CHIP payment gateway with FluentCart for seamless payment processing.
 * Version: 1.0.0
 * Author: CHIP IN SDN. BHD.
 * Author URI: https://www.chip-in.asia
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: chip-for-fluentcart
 * Domain Path: /languages
 * Reference: https://dev.fluentcart.com/modules/payment-methods
 * Reference: https://dev.fluentcart.com/payment-methods-integration/quick-implementation.html#step-4-create-javascript-file-for-frontend-checkout
 * 
 * Requires Plugins: fluent-cart
 */
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define plugin version
 */
define( 'CHIP_FOR_FLUENTCART_VERSION', '1.0.0' );

/**
 * Register CHIP payment gateway with FluentCart
 */
add_action('fluent_cart/register_payment_methods', function($app) {

	if (!function_exists('fluent_cart_api')) {
        return; // FluentCart not active
    }
	
	// Include CHIP payment gateway classes
	require_once plugin_dir_path( __FILE__ ) . 'includes/ChipLogger.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/ChipFluentCartApi.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/Chip.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/ChipSettingsBase.php';

	// Create and register your custom gateway
	$chipGateway = new \FluentCart\App\Modules\PaymentMethods\Chip\Chip();
    fluent_cart_api()->registerCustomPaymentMethod('chip', $chipGateway);
});

/**
 * Register init redirect handler using init hook (not admin-ajax)
 * This needs to be in the main plugin file to avoid wp-admin access issues
 */
add_action('init', function() {
	if (!function_exists('fluent_cart_api')) {
        return; // FluentCart not active
    }
	
	// Get the CHIP payment gateway instance using GatewayManager
	$chipGateway = \FluentCart\App\Modules\PaymentMethods\Core\GatewayManager::getInstance('chip');

	if ($chipGateway && method_exists($chipGateway, 'handleInitRedirect')) {
		$chipGateway->handleInitRedirect();
	}
}, 100, 0);