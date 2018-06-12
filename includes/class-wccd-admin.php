<?php

class wccd_admin {

	public function __construct() {
		add_action('admin_init', array($this, 'wccd_save_settings'));
		add_action('admin_menu', array($this, 'register_options_page'));
		add_action('wp_ajax_delete-certificate', array($this, 'delete_certificate_callback'));
		add_action('wp_ajax_add-cat', array($this, 'add_cat_callback'));
		add_action('admin_init', array($this, 'generate_cert_request'));
	}


	/**
	 * Registra la pagina opzioni del plugin
	 */
	public function register_options_page() {
		add_submenu_page( 'woocommerce', __('WooCommerce Carta docente - Impostazioni', 'wccd'), __('WC Carta Docente', 'wccd'), 'manage_options', 'settings', array($this, 'wccd_settings'));
	}


	/**
	 * Verifica la presenza del certificato
	 * @return string il certificato
	 */
	public function is_certificate_uploaded() {
		$files = [];
		foreach (glob(WCCD_PRIVATE . '*.pem') as $file) {
			$files[] = $file; 
		}
		$output = empty($files) ? false : $files[0];

		return $output;
	}


	/**
	 * Cancella il certificato
	 */
	public function delete_certificate_callback() {
		if(isset($_POST['delete'])) {
			$cert = isset($_POST['cert']) ? $_POST['cert'] : '';
			if($cert) {
				unlink(WCCD_PRIVATE . $cert);	
				delete_option('wccd-certificate-set');		
			}
		}

		exit;
	}


	/**
	 * Categoria per la verifica in fase di checkout
	 * @param  int   $n            il numero dell'elemento aggiunto
	 * @param  array $data         bene e categoria come chiave e velore
	 * @param  array $exclude_beni beni già utilizzati da escludere
	 * @param  array $exclude_cats categorie già utilizzate da escludere
	 * @return mixed]
	 */
	public function setup_cat($n, $data = null, $exclude_beni = null, $exclude_cats = null) {
		echo '<li class="setup-cat cat-' . $n . '">';

			$beni = array_diff(array('libri', 'testi', 'hardware', 'software'), explode(',', $exclude_beni));
			$terms = get_terms('product_cat', array('exclude' => $exclude_cats));

			$bene_value = is_array($data) ? key($data) : '';
			$term_value = $bene_value ? $data[$bene_value] : '';


			echo '<select name="wccd-beni-' . $n . '" class="wccd-field beni">';
				echo '<option value="">Bene carta docente</option>';
				foreach ($beni as $bene) {
    				echo '<option value="' . $bene . '"' . ($bene === $bene_value ? ' selected="selected"' : '') . '>' . ucfirst($bene) . '</option>';
				}
			echo '</select>';

			echo '<select name="wccd-categories-' . $n . '" class="wccd-field categories">';
				echo '<option value="">Categoria WooCommerce</option>';
				foreach ($terms as $term) {
    				echo '<option value="' . $term->term_id . '"' . ($term->term_id == $term_value ? ' selected="selected"' : '') . '>' . $term->name . '</option>';
				}
			echo '</select>';

			if($n === 1) {

				echo '<div class="add-cat-container">';
	    			echo '<img class="add-cat" src="' . WCCD_URI . 'images/add-cat.png">';
	    			echo '<img class="add-cat-hover" src="' . WCCD_URI . 'images/add-cat-hover.png">';
				echo '</div>';				

			} else {

    			echo '<div class="remove-cat-container">';
	    			echo '<img class="remove-cat" src="' . WCCD_URI . 'images/remove-cat.png">';
	    			echo '<img class="remove-cat-hover" src="' . WCCD_URI . 'images/remove-cat-hover.png">';
    			echo '</div>';

			}

		echo '</li>';
	}


	/**
	 * Aggiunge una nuova categoria per la verifica in fase di checkout
	 */
	public function add_cat_callback() {

		$number = isset($_POST['number']) ? sanitize_text_field($_POST['number']) : '';
		$exclude_beni = isset($_POST['exclude-beni']) ? sanitize_text_field($_POST['exclude-beni']) : '';
		$exclude_cats = isset($_POST['exclude-cats']) ? sanitize_text_field($_POST['exclude-cats']) : '';

		if($number) {
			$this->setup_cat($number, null, $exclude_beni, $exclude_cats);
		}

		exit;
	}


	/**
	 * Download della richiesta di certificato da utilizzare sul portale Carta del Docente
	 * Se non presenti, genera la chiave e la richiesta di certificato .der, 
	 */
	public function generate_cert_request() {
		if(isset($_POST['generate-der'])) {

			$cert_req_url = WCCD_PRIVATE . 'certificate-request.der';

			/*Crea il file .der se non presente*/
			if(!file_exists($cert_req_url)) {
				exec(WCCD_PRIVATE . 'wccd-generate-der.sh 2>&1', $out);				
			}

			/*Download file .der*/
			if($cert_req_url) {
		    	header('Content-Description: File Transfer');
			    header('Content-Type: application/octet-stream');
			    header("Content-Transfer-Encoding: binary");			    
	    		header("Content-disposition: attachment; filename=\"" . basename($cert_req_url) . "\""); 
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');

				readfile($cert_req_url); 

				exit;
			}
		}
	}


	/**
	 * Pagina opzioni plugin
	 */
	public function wccd_settings() {

		/*Recupero le opzioni salvate nel db*/
		$categories = get_option('wccd-categories');
		$tot_cats = count($categories);
		$wccd_image = get_option('wccd-image');

		echo '<div class="wrap">';
	    	echo '<div class="wrap-left">';
			    echo '<h1>WooCommerce Carta Docente - ' . esc_html(__('Impostazioni', 'wccd')) . '</h1>';

	    		/*Genera richiesta certificato .der*/
				echo '<table class="form-table wccd-table">';
		    		echo '<tr>';
		    			echo '<th scope="row">' . esc_html(__('Richiesta certificato', 'wccd')) . '</th>';
		    			echo '<td>';
		    				echo '<form method="post" action="">';
			    				echo '<input type="submit" name="generate-der" class="generate-der button" value="' . __('Scarica file .der', 'wccd') . '">';
		    				echo '</form>';
			    			echo '<p class="description">' . esc_html(__('Genera il file .der necessario per richiedere il certificato su Carta del docente', 'wccd')) . '</p>';
		    			echo '</td>';
		    		echo '</tr>';
	    		echo '</table>';

			    echo '<form name="wccd-upload-certificate" class="wccd-upload-certificate one-of" method="post" enctype="multipart/form-data" action="">';
			    	echo '<table class="form-table">';

			    		/*Carica certificato*/
			    		echo '<tr>';
			    			echo '<th scope="row">' . esc_html(__('Carica certificato', 'wccd')) . '</th>';
			    			echo '<td>';
			    				if($file = $this->is_certificate_uploaded()) {
			    					echo '<span class="cert-loaded">' . esc_html(basename($file)) . '</span>';
			    					echo '<a class="button delete delete-certificate">' . esc_html(__('Elimina'), 'wccd') . '</a>';
			    					echo '<p class="description">' . esc_html(__('File caricato correttamente.', 'wccd')) . '</p>';
			    				} else {
					    			echo '<input type="file" accept=".pem" name="wccd-certificate" class="wccd-certificate">';
					    			echo '<p class="description">' . esc_html(__('Carica il certificato (.pem) necessario alla connessione con Carta del docente', 'wccd')) . '</p>';
			    				}
			    			echo '</td>';
			    		echo '</tr>';

		    		echo '</table>';
			    	wp_nonce_field('wccd-upload-certificate', 'wccd-certificate-nonce');
			    	echo '<input type="hidden" name="wccd-certificate-hidden" value="1">';
			    	echo '<input type="submit" class="button-primary" value="' . esc_html('Salva certificato', 'wccd') . '">';
		    	echo '</form>';

			    echo '<form name="wccd-options" class="wccd-form wccd-options" method="post" enctype="multipart/form-data" action="">';
			    	echo '<table class="form-table">';
			    		
			    		echo '<tr>';
			    			echo '<th scope="row">' . esc_html(__('Categorie', 'wccd')) . '</th>';
			    			echo '<td>';

			    				echo '<ul  class="categories-container">';

			    					if($categories) {
			    						for ($i=1; $i <= $tot_cats ; $i++) { 
		    								$this->setup_cat($i, $categories[$i - 1]);
			    						}
			    					} else {
	    								$this->setup_cat(1);
			    					}

					    		echo '</ul>';
					    		echo '<input type="hidden" name="wccd-tot-cats" class="wccd-tot-cats" value="' . count($categories) . '">';
				    			echo '<p class="description">' . esc_html(__('Seleziona le categorie di prodotti corrispondenti ai beni acquistabili.', 'wccd')) . '</p>';
			    			echo '</td>';
			    		echo '</tr>';

						// echo '<tr>';
			    		// 	echo '<th scope="row">' . esc_html(__('xxxx', 'wccd')) . '</th>';
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


	/**
	 * Mostra un mesaggio d'errore nel caso in cui il certificato non isa valido
	 * @return string
	 */
	public function not_valid_certificate() {
		?>
		<div class="notice notice-error">
	        <p><?php esc_html_e(__( 'ATTENZIONE! Il file caricato non sembra essere un certificato valido.', 'wccd' )); ?></p>
	    </div>
		<?php
	}


	/**
	 * Salvatagiio delle impostazioni dell'utente
	 */
	public function wccd_save_settings() {

		if(isset($_POST['wccd-certificate-hidden']) && wp_verify_nonce($_POST['wccd-certificate-nonce'], 'wccd-upload-certificate')) {
			
			/*Carica certificato*/
			if(isset($_FILES['wccd-certificate'])) {
				$info = pathinfo($_FILES['wccd-certificate']['name']);
				$name = sanitize_file_name($info['basename']);
				if($info) {
					if($info['extension'] === 'pem') {
						move_uploaded_file($_FILES['wccd-certificate']['tmp_name'], WCCD_PRIVATE . $name);	
						update_option('wccd-certificate-set', 1);				
					} else {
						add_action('admin_notices', array($this, 'not_valid_certificate'));
					}					
				}
			}
		}

		if(isset($_POST['wccd-settings-hidden']) && wp_verify_nonce($_POST['wccd-settings-nonce'], 'wccd-save-settings')) {

			/*Impostazioni categorie per il controllo in fase di checkout*/
			if(isset($_POST['wccd-tot-cats'])) {
				$tot = sanitize_text_field($_POST['wccd-tot-cats']);

				$wccd_categories = array();

				for ($i=1; $i <= $tot ; $i++) { 
					$bene = isset($_POST['wccd-beni-' . $i]) ? $_POST['wccd-beni-' . $i] : '';
					$cat = isset($_POST['wccd-categories-' . $i]) ? $_POST['wccd-categories-' . $i] : '';

					if($bene && $cat) {
						$wccd_categories[] = array($bene => $cat);
					}
				}

				update_option('wccd-categories', $wccd_categories);
			}

			/*Immagine in pagina di checkout*/
			$wccd_image = isset($_POST['wccd-image']) ? sanitize_text_field($_POST['wccd-image']) : '';															
			update_option('wccd-image', $wccd_image);
		}
	}

}
new wccd_admin();