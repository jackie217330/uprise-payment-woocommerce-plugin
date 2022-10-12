<?php
/**
 * Multibanco APM class.
 *
 * @package wc_uprise_payment
 */

/**
 * Multibanco payment method class.
 *
 * @class WC_Gateway_Uprise_Payment_Alternative_Payments_Multibanco
 * @extends WC_Gateway_Uprise_Payment_Alternative_Payments
 */
class WC_Gateway_Uprise_Payment_Alternative_Payments_Multibanco extends WC_Gateway_Uprise_Payment_Alternative_Payments {

	/**
	 * Payment method name.
	 *
	 * @var string
	 */
	const PAYMENT_METHOD = 'multibanco';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'wc_uprise_payment_alternative_payments_multibanco';
		$this->method_title       = __( 'Uprise Payment', 'uprise-payment-woocommerce' );
		$this->method_description = __( 'The Uprise Payment extension allows shop owners to process online payments through the <a href=\"https://uprisepay.com\">Uprise Payment.</a>', 'uprise-payment-woocommerce' );
		$this->title              = __( 'Pay by Multibanco with Uprise', 'uprise-payment-woocommerce' );
		$this->supports           = [ 'products' ];
		$this->has_fields         = true;

		$this->init_form_fields();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	/**
	 * Payment form on checkout page.
	 */
	public function payment_fields() {
		// Get available apms depending on currency.
		$apm_available = WC_Checkoutcom_Utility::get_alternative_payment_methods();

		if ( ! in_array( self::PAYMENT_METHOD, $apm_available, true ) ) {
			?>
			<script>
				jQuery( '.payment_method_wc_uprise_payment_alternative_payments_multibanco' ).hide();
			</script>
			<?php
		}
	}

	/**
	 * Process Multibanco APM payment.
	 *
	 * @param  int $order_id Order ID.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		if ( ! session_id() ) {
			session_start();
		}

		$order = wc_get_order( $order_id );

		// Create alternative payment.
		$result = (array) WC_Checkoutcom_Api_Request::create_apm_payment( $order, self::PAYMENT_METHOD );

		// Check if result has error and return error message.
		if ( isset( $result['error'] ) && ! empty( $result['error'] ) ) {
			WC_Checkoutcom_Utility::wc_add_notice_self( $result['error'], 'error' );

			return;
		}

		// Redirect to apm if redirection url is available.
		if ( isset( $result['apm_redirection'] ) && ! empty( $result['apm_redirection'] ) ) {

			return [
				'result'   => 'success',
				'redirect' => $result['apm_redirection'],
			];
		}
	}

}
