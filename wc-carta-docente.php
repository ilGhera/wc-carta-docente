<?php
/**
 * Plugin name: WooCommerce Carta Docente - Premium
 * Plugin URI: https://www.ilghera.com/product/wc-carta-docente/
 * Description: Abilita in WooCommerce il pagamento con Carta del Docente prevista dallo stato Italiano. 
 * Author: ilGhera
 * Version: 1.2.3
 * Author URI: https://ilghera.com 
 * Requires at least: 4.0
 * Tested up to: 6.0
 * WC tested up to: 7
 * Text Domain: wccd
 * Domain Path: /languages
 */


/**
 * Attivazione
 */
function wccd_premium_activation() {

	/*Se presente, disattiva la versione free del plugin*/
	if(function_exists('wccd_activation')) {
		deactivate_plugins('wc-carta-docente/wc-carta-docente.php');
	    remove_action( 'plugins_loaded', 'wccd_activation' );
	    wp_redirect(admin_url('plugins.php?plugin_status=all&paged=1&s'));
	}

	/*WooCommerce è presente e attivo?*/
	if(!class_exists('WC_Payment_Gateway')) return;

	/*Definizione costanti*/
	define('WCCD_DIR', plugin_dir_path(__FILE__));
	define('WCCD_URI', plugin_dir_url(__FILE__));
	define('WCCD_INCLUDES', WCCD_DIR . 'includes/');
	define('WCCD_INCLUDES_URI', WCCD_URI . 'includes/');

	/*Main directory di upload*/
	$wp_upload_dir = wp_upload_dir();

	/*Creo se necessario la cartella wccd-private*/
	if( wp_mkdir_p( trailingslashit( $wp_upload_dir['basedir'] . '/wccd-private/files/backups' ) ) ) {
		define('WCCD_PRIVATE', $wp_upload_dir['basedir'] . '/wccd-private/');
		define('WCCD_PRIVATE_URI', $wp_upload_dir['baseurl'] . '/wccd-private/');
	}
	
	/*Requires*/
	require WCCD_INCLUDES . 'class-wccd-teacher-gateway.php';
	require WCCD_INCLUDES . 'class-wccd-soap-client.php';
	require WCCD_INCLUDES . 'class-wccd-admin.php';
	require WCCD_INCLUDES . 'class-wccd.php';

	/*Script e folgi di stile front-end*/
	function wccd_load_scripts() {
		wp_enqueue_style('wccd-style', WCCD_URI . 'css/wc-carta-docente.css');
		wp_enqueue_script('wccd-scripts', WCCD_URI . 'js/wc-carta-docente.js');
        wp_localize_script(
            'wccd-scripts',
            'wccdOptions',
            array(
                'ajaxURL'          => admin_url( 'admin-ajax.php' ),
                'couponConversion' => get_option( 'wccd-coupon' ),
            )
        );
	}

	/*Script e folgi di stile back-end*/
	function wccd_load_admin_scripts() {

        $admin_page = get_current_screen();

        if ( isset( $admin_page->base ) && 'woocommerce_page_wccd-settings' === $admin_page->base ) {

            wp_enqueue_style('wccd-admin-style', WCCD_URI . 'css/wc-carta-docente-admin.css');
            wp_enqueue_script('wccd-admin-scripts', WCCD_URI . 'js/wc-carta-docente-admin.js');

            /*tzCheckBox*/
            wp_enqueue_style( 'tzcheckbox-style', WCCD_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.css' );
            wp_enqueue_script( 'tzcheckbox', WCCD_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.js', array( 'jquery' ) );
            wp_enqueue_script( 'tzcheckbox-script', WCCD_URI . 'js/tzCheckbox/js/script.js', array( 'jquery' ) );

        }

	}

	/*Script e folgi di stile*/
	add_action('wp_enqueue_scripts', 'wccd_load_scripts');
	add_action('admin_enqueue_scripts', 'wccd_load_admin_scripts');

} 
add_action('plugins_loaded', 'wccd_premium_activation', 1);


/**
 * Update checker
 */
require( plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php');
$wccd_update_checker = Puc_v4_Factory::buildUpdateChecker(
    'https://www.ilghera.com/wp-update-server-2/?action=get_metadata&slug=wc-carta-docente-premium',
    __FILE__,
    'wc-carta-docente-premium'
);

$wccd_update_checker->addQueryArgFilter('wccd_secure_update_check');
function wccd_secure_update_check($queryArgs) {
    $key = base64_encode( get_option('wccd-premium-key') );

    if($key) {
        $queryArgs['premium-key'] = $key;
    }
    return $queryArgs;
}


/**
 * Avvisi utente in fase di aggiornaemnto plugin
 */
function wccd_update_message( $plugin_data, $response) {

	$message = null;
	$key = get_option('wccd-premium-key');

    $message = null;

	if(!$key) {

		$message = __('Per ricevere aggiornamenti devi inserire la tua <b>Premium Key</b> nelle <a href="' . admin_url() . 'admin.php/?page=wccd-settings">impostazioni del plugin</a>. Clicca <a href="https://www.ilghera.com/product/woocommerce-carta-docente-premium/" target="_blank">qui</a> per maggiori informazioni.', 'wccd');
	
	} else {
	
		$decoded_key = explode('|', base64_decode($key));
	    $bought_date = date( 'd-m-Y', strtotime($decoded_key[1]));
	    $limit = strtotime($bought_date . ' + 365 day');
	    $now = strtotime('today');

	    if($limit < $now) { 
	        $message = __('Sembra che la tua <strong>Premium Key</strong> sia scaduta. Clicca <a href="https://www.ilghera.com/product/woocommerce-carta-docente-premium/" target="_blank">qui</a> per maggiori informazioni.', 'wccd');
	    } elseif($decoded_key[2] != 3518) {
	    	$message = __('Sembra che la tua <strong>Premium Key</strong> non sia valida. Clicca <a href="https://www.ilghera.com/product/woocommerce-carta-docente-premium/" target="_blank">qui</a> per maggiori informazioni.', 'wccd');
	    }

	}

	$allowed = array(
		'b' => array(),
		'a' => array(
			'href'   => array(),
			'target' => array()
		),
	);

	echo ($message) ? '<br><span class="wccd-alert">' . wp_kses($message, $allowed) . '</span>' : '';

}
add_action('in_plugin_update_message-wc-carta-docente-premium/wc-carta-docente.php', 'wccd_update_message', 10, 2);
