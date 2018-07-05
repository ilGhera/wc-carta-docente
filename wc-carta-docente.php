<?php
/**
 * Plugin name: WooCommerce Carta Docente
 * Plugin URI: https://www.ilghera.com/product/wc-carta-docente/
 * Description: Abilita in WooCommerce il pagamento con Carta del Docente prevista dallo stato Italiano. 
 * Author: ilGhera
 * Version: 0.9.0
 * Author URI: https://ilghera.com 
 * Requires at least: 4.0
 * Tested up to: 4.9
 * WC tested up to: 3
 * Text Domain: wccd
 * Domain Path: /languages
 */


/*Definizione costanti*/
define('WCCD_DIR', plugin_dir_path(__FILE__));
define('WCCD_URI', plugin_dir_url(__FILE__));
define('WCCD_INCLUDES', WCCD_DIR . 'includes/');
define('WCCD_PRIVATE', WCCD_DIR . 'private/');


/*Script e folgi di stile front-end*/
function wccd_load_scripts() {
	wp_enqueue_style('wccd-style', WCCD_URI . 'css/wc-carta-docente.css');
}


/*Script e folgi di stile back-end*/
function wccd_load_admin_scripts() {
	wp_enqueue_style('wccd-admin-style', WCCD_URI . 'css/wc-carta-docente-admin.css');
	wp_enqueue_script('wccd-admin-scripts', WCCD_URI . 'js/wc-carta-docente-admin.js');
}


/*Attivazione*/
function wccd_premium_activation() {

	/*Se presente, disattiva la versione free del plugin*/
	if(function_exists('wccd_activation')) {
		deactivate_plugins('wc-carta-docente/wc-carta-docente.php');
	    remove_action( 'plugins_loaded', 'wccd_activation' );
	    wp_redirect(admin_url('plugins.php?plugin_status=all&paged=1&s'));
	}

	/*WooCommerce è presente e attivo?*/
	if(!class_exists('WC_Payment_Gateway')) return;
	
	/*Requires*/
	require WCCD_INCLUDES . 'class-wccd-teacher-gateway.php';
	require WCCD_INCLUDES . 'class-wccd-soap-client.php';
	require WCCD_INCLUDES . 'class-wccd-admin.php';

	/*Script e folgi di stile*/
	add_action('wp_enqueue_scripts', 'wccd_load_scripts');
	add_action('admin_enqueue_scripts', 'wccd_load_admin_scripts');
} 
add_action('plugins_loaded', 'wccd_activation', 1);


/*Update checker*/
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