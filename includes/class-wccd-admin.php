<?php

class wccd_admin {

	public function __construct() {
		add_action('admin_init', array($this, 'wccd_save_settings'));
		add_action('admin_menu', array($this, 'register_options_page'));
		add_action('wp_ajax_delete-certificate', array($this, 'delete_certificate_callback'));
		add_action('wp_ajax_add-cat', array($this, 'add_cat_callback'));
	}


	/**
	 * Registra la pagina opzioni del plugin
	 */
	public function register_options_page() {
		add_submenu_page( 'woocommerce', __('WooCommerce Carta docente - Impostazioni', 'wccd'), __('WC Carta Docente', 'wccd'), 'manage_options', 'wccd-settings', array($this, 'wccd_settings'));
	}


	/**
	 * Verifica la presenza di un file per estenzione
	 * @param string $ext l,estensione del file da cercare
	 * @return string l'url file
	 */
	public static function get_the_file($ext) {
		$files = [];
		foreach (glob(WCCD_PRIVATE . '*' . $ext) as $file) {
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
			$cert = isset($_POST['cert']) ? sanitize_text_field($_POST['cert']) : '';
			if($cert) {
				unlink(WCCD_PRIVATE . $cert);	
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
	 * Pulsante call to action Premium
	 */
	public function get_go_premium() {
		$output = '<span class="label label-warning premium">';
			$output .= '<a href="https://www.ilghera.com/woocommerce-carta-docente-premium" target="_blank">Premium</a>';
		$output .= '</span>';

		return $output;
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

				echo '<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"></div>';
				echo '<h2 id="wccd-admin-menu" class="nav-tab-wrapper woo-nav-tab-wrapper">';
					echo '<a href="#" data-link="wccd-certificate" class="nav-tab nav-tab-active" onclick="return false;">' . esc_html(__('Certificato', 'wccd')) . '</a>';
					echo '<a href="#" data-link="wccd-options" class="nav-tab" onclick="return false;">' . esc_html(__('Opzioni', 'wccd')) . '</a>';
				echo '</h2>';

			    /*Certificate*/
			    echo '<div id="wccd-certificate" class="wccd-admin" style="display: block;">';

		    		/*Carica certificato .pem*/
		    		echo '<h3>' . esc_html(__('Carica il tuo certificato', 'wccd')) . '</h3>';
	    			echo '<p class="description">' . esc_html(__('Se sei già in posseso di un certificato non devi fare altro che caricarlo, nient\'altro.', 'wccd')) . '</p>';

				    echo '<form name="wccd-upload-certificate" class="wccd-upload-certificate one-of" method="post" enctype="multipart/form-data" action="">';
				    	echo '<table class="form-table wccd-table">';

				    		/*Carica certificato*/
				    		echo '<tr>';
				    			echo '<th scope="row">' . esc_html(__('Carica certificato', 'wccd')) . '</th>';
				    			echo '<td>';
				    				if($file = self::get_the_file('.pem')) {
				    					echo '<span class="cert-loaded">' . esc_html(basename($file)) . '</span>';
				    					echo '<a class="button delete delete-certificate">' . esc_html(__('Elimina'), 'wccd') . '</a>';
				    					echo '<p class="description">' . esc_html(__('File caricato correttamente.', 'wccd')) . '</p>';
				    				} else {
						    			echo '<input type="file" accept=".pem" name="wccd-certificate" class="wccd-certificate">';
						    			echo '<p class="description">' . esc_html(__('Carica il certificato (.pem) necessario alla connessione con Carta del docente', 'wccd')) . '</p>';
			
								    	wp_nonce_field('wccd-upload-certificate', 'wccd-certificate-nonce');
								    	echo '<input type="hidden" name="wccd-certificate-hidden" value="1">';
								    	echo '<input type="submit" class="button-primary wccd-button" value="' . esc_html('Salva certificato', 'wccd') . '">';
				    				}
				    			echo '</td>';
				    		echo '</tr>';

			    		echo '</table>';
			    	echo '</form>';
	
				    /*Se il certificato non è presente vengono mostrati gli strumentui per generarlo*/
		    		if(!self::get_the_file('.pem')) {
				
			    		/*Genera richiesta certificato .der*/
			    		echo '<h3>' . esc_html(__('Richiedi un certificato', 'wccd')) . $this->get_go_premium() . '</h3>';
		    			echo '<p class="description">' . esc_html(__('Con questo strumento puoi generare un file .der necessario per richiedere il tuo certificato su Carta del docente.', 'wccd')) . '</p>';

	    				echo '<form id="generate-certificate-request" method="post" class="one-of" enctype="multipart/form-data" action="">';
							echo '<table class="form-table wccd-table">';
					    		echo '<tr>';
					    			echo '<th scope="row">' . esc_html(__('Stato', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="text" name="countryName" placeholder="IT" disabled>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Provincia', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="text" name="stateOrProvinceName" placeholder="Es. Milano" disabled>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Località', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="text" name="localityName" placeholder="Es. Legnano" disabled>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Nome azienda', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="text" name="organizationName" placeholder="Es. Taldeitali srl" disabled>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Reparto azienda', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="text" name="organizationalUnitName" placeholder="Es. Vendite" disabled>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Nome', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="text" name="commonName" placeholder="Es. Franco Bianchi" disabled>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Email', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="email" name="emailAddress" placeholder="Es. franco.bianchi@taldeitali.it" disabled>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Password', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="password" name="wccd-password" placeholder="**********" disabled>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row"></th>';
					    			echo '<td>';
					    			echo '<input type="hidden" name="generate-der-hidden" value="1">';
				    				echo '<input type="submit" name="generate-der" class="button-primary wccd-button generate-der" value="' . __('Scarica file .der', 'wccd') . '" disabled>';
					    			echo '</td>';
					    		echo '</tr>';

				    		echo '</table>';
	    				echo '</form>';


			    		/*Genera certificato .pem*/
			    		echo '<h3>' . esc_html(__('Crea il tuo certificato', 'wccd')) . $this->get_go_premium() . '</h3>';
		    			echo '<p class="description">' . esc_html(__('Con questo ultimo passaggio, potrai iniziare a ricevere pagamenti attraverso buoni del docente.', 'wccd')) . '</p>';

						echo '<form name="wccd-generate-certificate" class="wccd-generate-certificate" method="post" enctype="multipart/form-data" action="">';
					    	echo '<table class="form-table wccd-table">';

					    		/*Carica certificato*/
					    		echo '<tr>';
					    			echo '<th scope="row">' . esc_html(__('Genera certificato', 'wccd')) . '</th>';
					    			echo '<td>';
					    				
						    			echo '<input type="file" accept=".cer" name="wccd-cert" class="wccd-cert" disabled>';
						    			echo '<p class="description">' . esc_html(__('Carica il file .cer ottenuto da Carta del docente per procedere', 'wccd')) . '</p>';
								    	
								    	wp_nonce_field('wccd-generate-certificate', 'wccd-gen-certificate-nonce');
								    	echo '<input type="hidden" name="wccd-gen-certificate-hidden" value="1">';
								    	echo '<input type="submit" class="button-primary wccd-button" value="' . esc_html('Genera certificato', 'wccd') . '" disabled>';

					    			echo '</td>';
					    		echo '</tr>';

				    		echo '</table>';
				    	echo '</form>';			

					}

			    echo '</div>';


			    /*Options*/
			    echo '<div id="wccd-options" class="wccd-admin">';

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

	    	echo '<div class="wrap-right">';
				echo '<iframe width="300" height="900" scrolling="no" src="http://www.ilghera.com/images/wccd-iframe.html"></iframe>';
			echo '</div>';
			echo '<div class="clear"></div>';

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
	 * Salvataggio delle impostazioni dell'utente
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
					$bene = isset($_POST['wccd-beni-' . $i]) ? sanitize_text_field($_POST['wccd-beni-' . $i]) : '';
					$cat = isset($_POST['wccd-categories-' . $i]) ? sanitize_text_field($_POST['wccd-categories-' . $i]) : '';

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