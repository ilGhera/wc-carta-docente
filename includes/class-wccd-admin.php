<?php

class wccd_admin {

	public function __construct() {

		add_action('admin_init', array($this, 'wccd_save_settings'));
		add_action('admin_menu', array($this, 'register_options_page'));

	}

	public function register_options_page() {

		add_submenu_page( 'woocommerce', __('WooCommerce Carta docente - Impostazioni', 'wccd'), __('WC Carta Docente', 'wccd'), 'manage_options', 'settings', array($this, 'wccd_settings'));

	}

	public function is_certificate_uploaded() {
		$files = [];
		foreach (glob(WCCD_PRIVATE . '*.pem') as $file) {
			$files[] = $file; 
		}
		$output = empty($files) ? false : $files[0];

		return $output;
	}

	public function wccd_settings() {


		/*Recupero le opzioni salvate nel db*/
		$wccd_image = get_option('wccd-image');



		echo '<div class="wrap">';
	    	echo '<div class="wrap-left">';
			    echo '<h1>WooCommerce Carta Docente - ' . esc_html(__('Impostazioni', 'wccd')) . '</h1>';
			    echo '<form name="wccd-options" class="wccd-options" method="post" enctype="multipart/form-data" action="">';
			    	echo '<table class="form-table">';

			    		echo '<tr>';
			    			echo '<th scope="row">' . esc_html(__('Certificato', 'wccd')) . '</th>';
			    			echo '<td>';
			    				if($file = $this->is_certificate_uploaded()) {
			    					echo '/private/' . esc_html(basename($file));
			    					echo '<p class="description">' . esc_html(__('File caricato correttamente.', 'wccd')) . '</p>';
			    				} else {
					    			echo '<input type="file" accept=".pem" name="wccd-certificate" class="wccd-certificate">';
					    			echo '<p class="description">' . esc_html(__('Carica il certificato (.pem) necessario alla connessione con Carta del docente', 'wccd')) . '</p>';

			    				}
			    			echo '</td>';
			    		echo '</tr>';

			    		// echo '<tr>';
			    		// 	echo '<tr scope="row">' . esc_html(__('xxxx', 'wccd')) . '</th>';
			    		// 	echo '<td>';
				    	// 		echo '<input type="xxxx" name="xxxx" class="xxxx">';
				    	// 		echo '<p class="description">' . esc_html(__('xxxx', 'wccd')) . '</p>';
			    		// 	echo '</td>';
			    		// echo '</tr>';

			    		echo '<tr>';
			    			echo '<th scope="row">' . esc_html(__('Utilizzo immagine ', 'wccd')) . '</th>';
		    				echo '<td>';
				    			echo '<label>';
				    			echo '<input type="checkbox" name="wccd-image" value="1"' . ($wccd_image === '1' ? ' checked="checked"' : '') . '>';
				    			echo esc_html(__('Mostra il logo Carta del docente nella pagine di checkout.', 'wccd'));
				    			echo '</label>';
		    				echo '</td>';
			    		echo '</tr>';
			    	echo '</table>';
			    	wp_nonce_field('wccd-save-settings', 'wccd-settings-nonce');
			    	echo '<input type="hidden" name="wccd-settings-hidden" value="1">';
			    	echo '<input type="submit" class="button-primary" value="' . esc_html('Salva impostazioni', 'wccd') . '">';
			    echo '</form>';
		    echo '</div>';
	    echo '</div>';

	}

	public function not_valid_certificate() {
		?>
		<div class="notice notice-error">
	        <p><?php esc_html_e(__( 'ATTENZIONE! Il file caricato non sembra essere un certificato valido.', 'wccd' )); ?></p>
	    </div>
		<?php
	}

	public function wccd_save_settings() {
		if(isset($_POST['wccd-settings-hidden']) && wp_verify_nonce($_POST['wccd-settings-nonce'], 'wccd-save-settings')) {

			/*Certificato*/
			if(isset($_FILES['wccd-certificate'])) {
				$info = pathinfo($_FILES['wccd-certificate']['name']);
				$name = sanitize_file_name($info['basename']);
				if($info['extension'] === 'pem') {
					move_uploaded_file($_FILES['wccd-certificate']['tmp_name'], WCCD_PRIVATE . $name);					
				} else {
					add_action('admin_notices', array($this, 'not_valid_certificate'));
				}
			}
			// var_dump($_FILES);

			/*Immagine in pagina di checkout*/
			$wccd_image = isset($_POST['wccd-image']) ? sanitize_text_field($_POST['wccd-image']) : '';															
			update_option('wccd-image', $wccd_image);
		}
	}

}
new wccd_admin();