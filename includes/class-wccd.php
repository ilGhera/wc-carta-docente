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
	 * The constructor
	 *
	 * @return void
	 */
	public function __construct() {

		/* Filters */
		add_filter( 'woocommerce_payment_gateways', array( $this, 'wccd_add_teacher_gateway_class' ) );

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
	 * Se presente un certificato, aggiunge il nuovo gateway a quelli disponibili in WooCommerce
	 *
	 * @param array $methods gateways disponibili.
	 *
	 * @return array
	 */
	public function wccd_add_teacher_gateway_class( $methods ) {

		$sandbox = get_option( 'wccd-sandbox' );

		if ( $sandbox || ( wccd_admin::get_the_file( '.pem' ) && get_option( 'wccd-cert-activation' ) ) ) {

			$methods[] = 'WCCD_Teacher_Gateway';

		}

		return $methods;

	}

}

new WCCD();

