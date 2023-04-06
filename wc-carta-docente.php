<?php
/**
 * Plugin name: WooCommerce Carta Docente - Premium
 * Plugin URI: https://www.ilghera.com/product/wc-carta-docente/
 * Description: Abilita in WooCommerce il pagamento con Carta del Docente prevista dallo stato Italiano.
 * Author: ilGhera
 * @package wc-carta-docente
 * Version: 1.2.6
 * Author URI: https://ilghera.com 
 * Requires at least: 4.0
 * Tested up to: 6.1
 * WC tested up to: 7
 * Text Domain: wccd
 * Domain Path: /languages
 */

/**
 * Attivazione
 */
function wccd_premium_activation() {

	/*Se presente, disattiva la versione free del plugin*/
	if ( function_exists( 'wccd_activation' ) ) {
		deactivate_plugins( 'wc-carta-docente/wc-carta-docente.php' );
		remove_action( 'plugins_loaded', 'wccd_activation' );
		wp_safe_redirect( admin_url( 'plugins.php?plugin_status=all&paged=1&s' ) );
	}

	/*WooCommerce è presente e attivo?*/
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	/*Definizione costanti*/
	define( 'WCCD_DIR', plugin_dir_path( __FILE__ ) );
	define( 'WCCD_URI', plugin_dir_url( __FILE__ ) );
	define( 'WCCD_INCLUDES', WCCD_DIR . 'includes/' );
	define( 'WCCD_INCLUDES_URI', WCCD_URI . 'includes/' );
	define( 'WCCD_VERSION', '0.9.3' );

	/*Main directory di upload*/
	$wp_upload_dir = wp_upload_dir();

	/*Creo se necessario la cartella wccd-private*/
	if ( wp_mkdir_p( trailingslashit( $wp_upload_dir['basedir'] . '/wccd-private/files/backups' ) ) ) {
		define( 'WCCD_PRIVATE', $wp_upload_dir['basedir'] . '/wccd-private/' );
		define( 'WCCD_PRIVATE_URI', $wp_upload_dir['baseurl'] . '/wccd-private/' );
	}

	/*Requires*/
	require WCCD_INCLUDES . 'ilghera-notice/class-ilghera-notice.php';
	require WCCD_INCLUDES . 'class-wccd-teacher-gateway.php';
	require WCCD_INCLUDES . 'class-wccd-soap-client.php';
	require WCCD_INCLUDES . 'class-wccd-admin.php';
	require WCCD_INCLUDES . 'class-wccd.php';
	require WCCD_INCLUDES . 'ilghera-notice/class-ilghera-notice.php';

	/**
	 * Script e folgi di stile front-end
	 *
	 * @return void
	 */
	function wccd_load_scripts() {
		wp_enqueue_style( 'wccd-style', WCCD_URI . 'css/wc-carta-docente.css', array(), WCCD_VERSION );
		wp_enqueue_script( 'wccd-scripts', WCCD_URI . 'js/wc-carta-docente.js', array(), WCCD_VERSION, false );
		wp_localize_script(
			'wccd-scripts',
			'wccdOptions',
			array(
				'ajaxURL'          => admin_url( 'admin-ajax.php' ),
				'couponConversion' => get_option( 'wccd-coupon' ),
			)
		);
	}

	/**
	 * Script e folgi di stile back-end
	 *
	 * @return void
	 */
	function wccd_load_admin_scripts() {

		$admin_page = get_current_screen();

		if ( isset( $admin_page->base ) && 'woocommerce_page_wccd-settings' === $admin_page->base ) {

			wp_enqueue_style( 'wccd-admin-style', WCCD_URI . 'css/wc-carta-docente-admin.css', array(), WCCD_VERSION );
			wp_enqueue_script( 'wccd-admin-scripts', WCCD_URI . 'js/wc-carta-docente-admin.js', array(), WCCD_VERSION, false );

			/* Nonce per l'eliminazione del certificato */
			$delete_nonce  = wp_create_nonce( 'wccd-del-cert-nonce' );
			$add_cat_nonce = wp_create_nonce( 'wccd-add-cat-nonce' );

			wp_localize_script(
				'wccd-admin-scripts',
				'wccdData',
				array(
					'delCertNonce' => $delete_nonce,
					'addCatNonce'  => $add_cat_nonce,
				)
			);

			/*tzCheckBox*/
			wp_enqueue_style( 'tzcheckbox-style', WCCD_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.css', array(), WCCD_VERSION );
			wp_enqueue_script( 'tzcheckbox', WCCD_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.js', array( 'jquery' ), WCCD_VERSION, false );
			wp_enqueue_script( 'tzcheckbox-script', WCCD_URI . 'js/tzCheckbox/js/script.js', array( 'jquery' ), WCCD_VERSION, false );

		}

	}

	/*Script e folgi di stile*/
	add_action( 'wp_enqueue_scripts', 'wccd_load_scripts' );
	add_action( 'admin_enqueue_scripts', 'wccd_load_admin_scripts' );

}
add_action( 'plugins_loaded', 'wccd_premium_activation', 1 );


/**
 * Update checker
 */
require plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$wccd_update_checker = PucFactory::buildUpdateChecker(
	'https://www.ilghera.com/wp-update-server-2/?action=get_metadata&slug=wc-carta-docente-premium',
	__FILE__,
	'wc-carta-docente-premium'
);

$wccd_update_checker->addQueryArgFilter( 'wccd_secure_update_check' );

/**
 * PUC Secure update check
 *
 * @param array $query_args the parameters.
 *
 * @return array
 */
function wccd_secure_update_check( $query_args ) {
	$key = base64_encode( get_option( 'wccd-premium-key' ) );

	if ( $key ) {
		$query_args['premium-key'] = $key;
	}
	return $query_args;
}


/**
 * Avvisi utente in fase di aggiornaemnto plugin
 *
 * @param array  $plugin_data the plugin metadata.
 * @param object $response    metadata about the available plugin update.
 *
 * @return void
 */
function wccd_update_message( $plugin_data, $response ) {

	$message = null;
	$key     = get_option( 'wccd-premium-key' );

	$message = null;

	if ( ! $key ) {

		/* Translators: the admin URL */
		$message = sprintf( __( 'Per ricevere aggiornamenti devi inserire la tua <b>Premium Key</b> nelle <a href="%sadmin.php/?page=wccd-settings">impostazioni del plugin</a>. Clicca <a href="https://www.ilghera.com/product/woocommerce-carta-docente-premium/" target="_blank">qui</a> per maggiori informazioni.', 'wccd' ), admin_url() );

	} else {

		$decoded_key = explode( '|', base64_decode( $key ) );
		$bought_date = date( 'd-m-Y', strtotime( $decoded_key[1] ) );
		$limit       = strtotime( $bought_date . ' + 365 day' );
		$now         = strtotime( 'today' );

		if ( $limit < $now ) {
			$message = __( 'Sembra che la tua <strong>Premium Key</strong> sia scaduta. Clicca <a href="https://www.ilghera.com/product/woocommerce-carta-docente-premium/" target="_blank">qui</a> per maggiori informazioni.', 'wccd' );
		} elseif ( 3518 !== intval( $decoded_key[2] ) ) {
			$message = __( 'Sembra che la tua <strong>Premium Key</strong> non sia valida. Clicca <a href="https://www.ilghera.com/product/woocommerce-carta-docente-premium/" target="_blank">qui</a> per maggiori informazioni.', 'wccd' );
		}
	}

	$allowed = array(
		'b' => array(),
		'a' => array(
			'href'   => array(),
			'target' => array(),
		),
	);

	echo ( $message ) ? '<br><span class="wccd-alert">' . wp_kses( $message, $allowed ) . '</span>' : '';

}
add_action( 'in_plugin_update_message-wc-carta-docente-premium/wc-carta-docente.php', 'wccd_update_message', 10, 2 );
