<?php
/**
 * CHIP Logger for FluentCart
 *
 * @package    Chip_For_Fluentcart
 * @subpackage Chip_For_Fluentcart/includes
 */

namespace FluentCart\App\Modules\PaymentMethods\Chip;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ChipLogger {
	
	public function log( $message, $logStatus = 'info', $otherInfo = [] ) {
		if ( function_exists( 'fluent_cart_add_log' ) ) {
			fluent_cart_add_log(
				'CHIP for FluentCart',
				$message,
				$logStatus,
				$otherInfo
			);
		}
	}
}

