<?php
include_once __DIR__ . '/../templates/class-wc-checkoutcom-apm-templates.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-ideal.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-alipay.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-qpay.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-boleto.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-sepa.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-knet.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-bancontact.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-eps.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-poli.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-klarna.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-sofort.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-fawry.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-giropay.php';
include_once __DIR__ . '/apms/class-wc-gateway-checkout-com-alternative-payments-multibanco.php';

/**
 * Class WC_Gateway_Checkout_Com_Alternative_Payments for Alternative Payment method main class.
 */
class WC_Gateway_Checkout_Com_Alternative_Payments extends WC_Payment_Gateway {

	/**
	 * WC_Gateway_Checkout_Com_Alternative_Payments constructor.
	 */
	public function __construct() {
		$this->id                 = 'wc_checkout_com_alternative_payments';
		$this->method_title       = __( 'Checkout.com', 'wc_checkout_com' );
		$this->method_description = __( 'The Checkout.com extension allows shop owners to process online payments through the <a href="https://www.checkout.com">Checkout.com Payment Gateway.</a>', 'wc_checkout_com' );
		$this->title              = __( 'Alternative Payment', 'wc_checkout_com' );

		$this->has_fields = true;
		$this->supports   = [ 'products', 'refunds' ];

		$this->init_form_fields();
		$this->init_settings();

		// Turn these settings into variables we can use.
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

		// Generate token.
		add_action( 'woocommerce_api_wc_checkoutcom_googlepay_token', [ $this, 'generate_token' ] );
	}

	/**
	 * Show module configuration in backend.
	 *
	 * @return string|void
	 */
	public function init_form_fields() {
		$this->form_fields = WC_Checkoutcom_Cards_Settings::apm_settings();
		$this->form_fields = array_merge(
			$this->form_fields,
			[
				'screen_button' => [
					'id'    => 'screen_button',
					'type'  => 'screen_button',
					'title' => __( 'Other Settings', 'wc_checkout_com' ),
				],
			]
		);
	}

	/**
	 * Generate links for the admin page.
	 *
	 * @param string $key The key.
	 * @param array  $value The value.
	 */
	public function generate_screen_button_html( $key, $value ) {
		WC_Checkoutcom_Admin::generate_links( $key, $value );
	}

	/**
	 * Show frames js on checkout page.
	 */
	public function payment_fields() {
		?>
			<script>
				jQuery('.payment_method_wc_checkout_com_alternative_payments').hide();
			</script>
		<?php
	}

	/**
	 * Process refund for the order.
	 *
	 * @param int    $order_id Order ID.
	 * @param int    $amount   Amount to refund.
	 * @param string $reason   Refund reason.
	 *
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		$order  = wc_get_order( $order_id );
		$result = (array) WC_Checkoutcom_Api_Request::refund_payment( $order_id, $order );

		// check if result has error and return error message.
		if ( isset( $result['error'] ) && ! empty( $result['error'] ) ) {
			WC_Checkoutcom_Utility::wc_add_notice_self( $result['error'] );

			return false;
		}

		// Set action id as woo transaction id.
		update_post_meta( $order_id, '_transaction_id', $result['action_id'] );
		update_post_meta( $order_id, 'cko_payment_refunded', true );

		if ( isset( $_SESSION['cko-refund-is-less'] ) ) {
			if ( $_SESSION['cko-refund-is-less'] ) {
				/* translators: %s: Action ID. */
				$order->add_order_note( sprintf( esc_html__( 'Checkout.com Payment Partially refunded from Admin - Action ID : %s', 'wc_checkout_com' ), $result['action_id'] ) );

				unset( $_SESSION['cko-refund-is-less'] );

				return true;
			}
		}

		/* translators: %s: Action ID. */
		$order->add_order_note( sprintf( esc_html__( 'Checkout.com Payment refunded from Admin - Action ID : %s', 'wc_checkout_com' ), $result['action_id'] ) );

		// when true is returned, status is changed to refunded automatically.
		return true;
	}
}
