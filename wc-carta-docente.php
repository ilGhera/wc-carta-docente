<?php
/**
 * Plugin name: WooCommerce Carta Docente
 * Plugin URI: https://www.ilghera.com/product/wc-carta-docente/
 * Description: Abilita in WooCommerce il pagamento con Carta del Docente prevista dallo stato Italiano. 
 * Author: ilGhera
 * Version: 0.9.3
 * Author URI: https://ilghera.com 
 * Requires at least: 4.0
 * Tested up to: 5
 * WC tested up to: 3
 * Text Domain: wccd
 * Domain Path: /languages
 */


/*Attivazione*/
function wccd_activation() {

	/*Is WooCommerce activated?*/
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

	/*Script e folgi di stile front-end*/
	function wccd_load_scripts() {
		wp_enqueue_style('wccd-style', WCCD_URI . 'css/wc-carta-docente.css');
	}


	/*Script e folgi di stile back-end*/
	function wccd_load_admin_scripts() {
		wp_enqueue_style('wccd-admin-style', WCCD_URI . 'css/wc-carta-docente-admin.css');
		wp_enqueue_script('wccd-admin-scripts', WCCD_URI . 'js/wc-carta-docente-admin.js');
	}

	/*Script e folgi di stile*/
	add_action('wp_enqueue_scripts', 'wccd_load_scripts');
	add_action('admin_enqueue_scripts', 'wccd_load_admin_scripts');
} 
add_action('plugins_loaded', 'wccd_activation', 100);