<?php
/**
 * Plugin name: WooCommerce Carta Docente
 * Abilita in WooCommerce il pagamento con la Carta del Docente prevista dallo stato Italiano. 
 */


/*Define plugin constants*/
define('WCCD_DIR', plugin_dir_path(__FILE__));
define('WCCD_URI', plugin_dir_url(__FILE__));
define('WCCD_INCLUDES', WCCD_DIR . 'includes/');
define('WCCD_PRIVATE', WCCD_DIR . 'private/');


/*Init function*/
function init_WC_Teacher_Gateway() {

	/*Is WooCommerce activated?*/
	if(!class_exists('WC_Payment_Gateway')) return;
	
	/*Requires*/
	require WCCD_INCLUDES . 'class-wc-teacher-gateway.php';
	require WCCD_INCLUDES . 'class-wccd-soap-client.php';

} 
add_action('plugins_loaded', 'init_WC_Teacher_Gateway');


