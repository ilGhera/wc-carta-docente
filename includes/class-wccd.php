<?php
/**
 * Class WCCD
 *
 * @author ilGhera
 * @package wc-carta-docente/includes
 * @since 1.3.0
 */

/**
 * WCCD class
 */
class WCCD {

	/**
	 * Coupon option
	 *
	 * @var bool
	 */
	public $coupon_option;

	/**
	 * Orders on hold option
	 *
	 * @var bool
	 */
	public $orders_on_hold;


	/**
	 * The constructor
	 *
	 * @return void
	 */
	public function __construct() {

		$this->coupon_option  = get_option( 'wccd-coupon' );
		$this->orders_on_hold = get_option( 'wccd-orders-on-hold' );

		/* Filters */
		add_filter( 'woocommerce_payment_gateways', array( $this, 'wccd_add_teacher_gateway_class' ) );

		/* Actions */
		add_action( 'wp_ajax_check-for-coupon', array( $this, 'wccd_check_for_coupon' ) );
		add_action( 'wp_ajax_nopriv_check-for-coupon', array( $this, 'wccd_check_for_coupon' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'process_coupon' ) );

		if ( $this->orders_on_hold ) {

			add_action( 'woocommerce_order_status_completed', array( $this, 'complete_process_code' ), 10, 1 );

		}

	}


	/**
	 * Restituisce i dati della sessione WC corrente
	 *
	 * @return array
	 */
	public function get_session_data() {

		$session = WC()->session;

		if ( $session ) {

			return $session->get_session_data();

		}

	}


	/**
	 * Verifica se in sessione è stato applicato un coupon derivante da un buono Carta del Docente
	 *
	 * @param bool $return restituisce il codice del coupon se valorizzato.
	 *
	 * @return mixed
	 */
	public function wccd_coupon_applied( $return = false ) {

		$output       = false;
		$session_data = $this->get_session_data();

		if ( $session_data ) {

			$coupons = isset( $session_data['applied_coupons'] ) ? maybe_unserialize( $session_data['applied_coupons'] ) : null;

			if ( $coupons && is_array( $coupons ) ) {

				foreach ( $coupons as $coupon ) {

					if ( false !== strpos( $coupon, 'wccd' ) ) {

						if ( $return ) {

							$output = $coupon;

						} else {

							$output = true;

						}

						continue;

					}
				}
			}
		}

		return $output;

	}


	/**
	 * Verifica di un coupon wccd in sessione al click di aquisto in pagina di checkout
	 *
	 * @return void
	 */
	public function wccd_check_for_coupon() {

		echo esc_html( $this->wccd_coupon_applied() );

		exit;

	}


	/**
	 * Se presente un certificato, aggiunge il nuovo gateway a quelli disponibili in WooCommerce
	 *
	 * @param array $methods gateways disponibili.
	 *
	 * @return array
	 */
	public function wccd_add_teacher_gateway_class( $methods ) {

		$available = ( $this->coupon_option && $this->wccd_coupon_applied() ) ? false : true;
		$sandbox   = get_option( 'wccd-sandbox' );

		if ( $available ) {

			if ( $sandbox || ( wccd_admin::get_the_file( '.pem' ) && get_option( 'wccd-cert-activation' ) ) ) {

				$methods[] = 'WCCD_Teacher_Gateway';

			}
		}

		return $methods;

	}


	/**
	 * Durante la creazione dell'ordine se presente un coupon wccd invia il buono a Carta del Docente
	 * L'ordine viene bloccato se il buono non risulta essere valido
	 *
	 * @return void
	 */
	public function process_coupon() {

		if ( $this->coupon_option ) {

			$coupon_code = $this->wccd_coupon_applied( true );

			if ( $coupon_code ) {

				$parts         = explode( '-', $coupon_code );
				$coupon        = new WC_Coupon( $coupon_code );
				$coupon_amount = $coupon->get_amount();
				$teacher_code  = $coupon->get_description();

				$notice = WCCD_Teacher_Gateway::process_code( $parts[1], $teacher_code, $coupon_amount, true );

				if ( 1 !== intval( $notice ) ) {

					/* Translators: Notifica all'utente nella pagina di checkout */
					wc_add_notice( sprintf( __( 'Buono docente - %s', 'wccd' ), $notice ), 'error' );

				} else {

					/* Eliminazione ordine temporaneo */
					wp_delete_post( $parts[1] );

				}
			}
		}

	}


	/**
	 * Customer email for failed order
	 *
	 * @param int    $order_id the order ID.
	 * @param object $order    the order.
	 *
	 * @return void
	 */
	public function refused_code_customer_notification( $order_id, $order ) {

		/* Get options */
		$subject = get_option( 'wccd-email-subject' );
		$subject = $subject ? $subject : __( 'Ordine fallito' );
		$heading = get_option( 'wccd-email-heading' );
		$heading = $heading ? $heading : __( 'Ordine fallito' );

		/* Get WooCommerce email objects */
		$mailer = WC()->mailer()->get_emails();

		/* Set custom details */
		$mailer['WC_Email_Customer_On_Hold_Order']->settings['heading'] = $heading;
		$mailer['WC_Email_Customer_On_Hold_Order']->settings['subject'] = $subject;

		/* Send the email with custom heading & subject */
		$mailer['WC_Email_Customer_On_Hold_Order']->trigger( $order_id );

	}


	/**
	 * Process the code when completing the order manually
	 *
	 * @param int $order_id the order ID.
	 *
	 * @return void
	 */
	public function complete_process_code( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( is_object( $order ) && ! is_wp_error( $order ) ) {

			if ( 'docente' === $order->get_payment_method() ) {

				$teacher_code = $order->get_meta( 'wc-codice-docente' );
				$total        = $order->get_total();
				$validate     = WCCD_Teacher_Gateway::process_code( $order_id, $teacher_code, $total, false, true );

				if ( 1 !== intval( $validate ) ) {

					$order->update_status( 'wc-failed' );

					/* Send email to customer */
					$this->refused_code_customer_notification( $order_id, $order );

					/* Don't send complete order email to customer */
					add_filter(
						'woocommerce_email_enabled_customer_completed_order',
						function() {
							return false;
						}
					);

				}
			}
		}

	}

}

new WCCD();

