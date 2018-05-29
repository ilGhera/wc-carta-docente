<?php
/**
 * Plugin name: WooCommerce Carta Docente
 * Plugin URI: https://www.ilghera.com/product/wc-carta-docente/
 * Description: Abilita in WooCommerce il pagamento con la Carta del Docente prevista dallo stato Italiano. 
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


/*Init*/
function init_WC_Teacher_Gateway() {

	/*Is WooCommerce activated?*/
	if(!class_exists('WC_Payment_Gateway')) return;
	
	/*Requires*/
	require WCCD_INCLUDES . 'class-wc-teacher-gateway.php';
	require WCCD_INCLUDES . 'class-wccd-soap-client.php';
	require WCCD_INCLUDES . 'class-wccd-admin.php';

} 
add_action('plugins_loaded', 'init_WC_Teacher_Gateway');

