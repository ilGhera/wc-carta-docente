<?php
/**
 * Pagina opzioni e gestione certificati
 * @author ilGhera
 * @package wc-carta-docente/includes
 * @version 0.9.2
 */
class wccd_admin {

	public function __construct() {
		add_action('admin_init', array($this, 'wccd_save_settings'));
		add_action('admin_init', array($this, 'generate_cert_request'));
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
	 * Restituisce il nome esatto del bene Carta del Docente partendo dallo slug
	 * @param  array $beni       l'elenco dei beni di carta del docente
	 * @param  string $bene_slug lo slug del bene
	 * @return string
	 */
	public function get_bene_lable($beni, $bene_slug) {
		foreach ($beni as $bene) {
			if(sanitize_title($bene) === $bene_slug) {
				return $bene;
			}
		}
	}


	/**
	 * Categoria per la verifica in fase di checkout
	 * @param  int   $n            il numero dell'elemento aggiunto
	 * @param  array $data         bene e categoria come chiave e velore
	 * @param  array $exclude_beni beni già utilizzati da escludere
	 * @return mixed]
	 */
	public function setup_cat($n, $data = null, $exclude_beni = null) {
		echo '<li class="setup-cat cat-' . $n . '">';

			/*L'elenco dei beni dei vari ambiti previsti dalla piattaforma*/
			$beni_index = array(
				'Abbonamento/Card',
				'Bigletto d\'igresso',
				'Hardware',
				'Software',
				'Libri',
				'Riviste e pubblicazioni'
			);

			$beni_prepared = array_map('sanitize_title', $beni_index); 

			$beni = array_diff($beni_prepared, explode(',', $exclude_beni));
			$terms = get_terms('product_cat');

			$bene_value = is_array($data) ? key($data) : '';
			$term_value = $bene_value ? $data[$bene_value] : '';


			echo '<select name="wccd-beni-' . $n . '" class="wccd-field beni">';
				echo '<option value="">Bene carta docente</option>';
				foreach ($beni as $bene) {
    				echo '<option value="' . $bene . '"' . ($bene === $bene_value ? ' selected="selected"' : '') . '>' . $this->get_bene_lable($beni_index, $bene) . '</option>';
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

		if($number) {
			$this->setup_cat($number, null, $exclude_beni);
		}

		exit;
	}


	/**
	 * Trasforma il contenuto di un certificato .pem in .der
	 * @param  string $pem_data il certificato .pem
	 * @return string           
	 */
	public function pem2der($pem_data) {
	   $begin = "-----BEGIN CERTIFICATE REQUEST-----";
	   $end   = "-----END CERTIFICATE REQUEST-----";
	   $pem_data = substr($pem_data, strpos($pem_data, $begin)+strlen($begin));   
	   $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
	   $der = base64_decode($pem_data);
	   return $der;
	}


	/**
	 * Download della richiesta di certificato da utilizzare sul portale Carta del Docente
	 * Se non presenti, genera la chiave e la richiesta di certificato .der, 
	 */
	public function generate_cert_request() {

		if(isset($_POST['generate-der-hidden'])) {

			$cert_req_url = WCCD_PRIVATE . 'files/certificate-request.der';

			/*Crea il file .der se non presente*/
			if(!file_exists($cert_req_url)) {

	            $countryName = isset($_POST['countryName']) ? sanitize_text_field($_POST['countryName']) : '';
	            $stateOrProvinceName = isset($_POST['stateOrProvinceName']) ? sanitize_text_field($_POST['stateOrProvinceName']) : '';
	            $localityName = isset($_POST['localityName']) ? sanitize_text_field($_POST['localityName']) : '';
	            $organizationName = isset($_POST['organizationName']) ? sanitize_text_field($_POST['organizationName']) : '';
	            $organizationalUnitName = isset($_POST['organizationalUnitName']) ? sanitize_text_field($_POST['organizationalUnitName']) : '';
	            $commonName = isset($_POST['commonName']) ? sanitize_text_field($_POST['commonName']) : '';
	            $emailAddress = isset($_POST['emailAddress']) ? sanitize_text_field($_POST['emailAddress']) : '';
	            $wccd_password = isset($_POST['wccd-password']) ? sanitize_text_field($_POST['wccd-password']) : '';

	            /*Salvo passw nel db*/
	            if($wccd_password) {
	            	update_option('wccd-password', base64_encode($wccd_password));
	            }

				$dn = array(
                    "countryName" => $countryName,
                    "stateOrProvinceName" => $stateOrProvinceName,
                    "localityName" => $localityName,
                    "organizationName" => $organizationName,
                    "organizationalUnitName" => $organizationalUnitName,
                    "commonName" => $commonName,
                    "emailAddress" => $emailAddress
                );


                /*Genera la private key*/
                $privkey = openssl_pkey_new(array(
                    "private_key_bits" => 2048,
                    "private_key_type" => OPENSSL_KEYTYPE_RSA,
                ));


                /*Genera ed esporta la richiesta di certificato .pem*/
                $csr = openssl_csr_new($dn, $privkey, array('digest_alg' => 'sha256'));
                openssl_csr_export_to_file($csr, WCCD_PRIVATE . 'files/certificate-request.pem');


                /*Trasforma la richiesta di certificato in .der e la esporta*/
                $csr_der = $this->pem2der(file_get_contents(WCCD_PRIVATE . 'files/certificate-request.pem'));
                file_put_contents(WCCD_PRIVATE . 'files/certificate-request.der', $csr_der);
                
                /*Esporta la private key*/
                // openssl_csr_export_to_file($csr_der, WCCD_PRIVATE . 'files/certificate-request.der');
                openssl_pkey_export_to_file($privkey, WCCD_PRIVATE . 'files/key.der');

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
	 * Attivazione certificato
	 */
	public function wccd_cert_activation() {
	    $soapClient = new wccd_soap_client('11aa22bb', '');

	    try {

		    $operation = $soapClient->check(1);
		    return 'ok';

		} catch(Exception $e) {

            $notice = isset($e->detail->FaultVoucher->exceptionMessage) ? $e->detail->FaultVoucher->exceptionMessage : $e->faultstring;
		    error_log('Error wccd_cert_activation: ' . print_r($e, true));
		    return $notice;

        } 
	}


	/**
	 * Pagina opzioni plugin
	 */
	public function wccd_settings() {

		/*Recupero le opzioni salvate nel db*/
		$premium_key = get_option('wccd-premium-key');
		$passphrase = base64_decode(get_option('wccd-password'));
		$categories = get_option('wccd-categories');
		$tot_cats = $categories ? count($categories) : 0;
		$wccd_image = get_option('wccd-image');

		echo '<div class="wrap">';
	    	echo '<div class="wrap-left">';
			    echo '<h1>WooCommerce Carta Docente - ' . esc_html(__('Impostazioni', 'wccd')) . '</h1>';

			     /*Premium key form*/
			    echo '<form method="post" action="">';
			    	echo '<table class="form-table wccd-table">';
						echo '<th scope="row">' . __('Premium Key', 'wccd') . '</th>';
						echo '<td>';
							echo '<input type="text" class="regular-text" name="wccd-premium-key" id="wccd-premium-key" placeholder="' . __('Inserisci la tua Premium Key', 'wccd' ) . '" value="' . $premium_key . '" />';
							echo '<p class="description">' . __('Aggiungi la tua Premium Key e mantieni aggiornato <strong>Woocommerce Carta Docente - Premium</strong>.', 'wccd') . '</p>';
					    	wp_nonce_field('wccd-premium-key', 'wccd-premium-key-nonce');
							echo '<input type="hidden" name="premium-key-sent" value="1" />';
							echo '<input type="submit" class="button button-primary wccd-button"" value="' . __('Salva ', 'wccd') . '" />';
						echo '</td>';
					echo '</table>';
				echo '</form>';

				/*Tabs*/
				echo '<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"></div>';
				echo '<h2 id="wccd-admin-menu" class="nav-tab-wrapper woo-nav-tab-wrapper">';
					echo '<a href="#" data-link="wccd-certificate" class="nav-tab nav-tab-active" onclick="return false;">' . esc_html(__('Certificato', 'wccd')) . '</a>';
					echo '<a href="#" data-link="wccd-options" class="nav-tab" onclick="return false;">' . esc_html(__('Opzioni', 'wccd')) . '</a>';
				echo '</h2>';

			    /*Certificate*/
			    echo '<div id="wccd-certificate" class="wccd-admin" style="display: block;">';

		    		/*Carica certificato .pem*/
		    		echo '<h3>' . esc_html(__('Carica il tuo certificato', 'wccd')) . '</h3>';
	    			echo '<p class="description">' . esc_html(__('Se sei già in posseso di un certificato non devi fare altro che caricarlo con relativa password, nient\'altro.', 'wccd')) . '</p>';

				    echo '<form name="wccd-upload-certificate" class="wccd-upload-certificate one-of" method="post" enctype="multipart/form-data" action="">';
				    	echo '<table class="form-table wccd-table">';

				    		/*Carica certificato*/
				    		echo '<tr>';
				    			echo '<th scope="row">' . esc_html(__('Carica certificato', 'wccd')) . '</th>';
				    			echo '<td>';
				    				if($file = self::get_the_file('.pem')) {

				    					$activation = $this->wccd_cert_activation();

				    					if($activation === 'ok') {

					    					echo '<span class="cert-loaded">' . esc_html(basename($file)) . '</span>';
					    					echo '<a class="button delete delete-certificate">' . esc_html(__('Elimina'), 'wccd') . '</a>';
					    					echo '<p class="description">' . esc_html(__('File caricato e attivato correttamente.', 'wccd')) . '</p>';

					    					update_option('wccd-cert-activation', 1);

				    					} else {

					    					echo '<span class="cert-loaded error">' . esc_html(basename($file)) . '</span>';
					    					echo '<a class="button delete delete-certificate">' . esc_html(__('Elimina'), 'wccd') . '</a>';
					    					echo '<p class="description">' . sprintf(esc_html(__('L\'attivazione del certificato ha restituito il seguente errore: %s', 'wccd')), $activation) . '</p>';

					    					delete_option('wccd-cert-activation');

				    					}

				    				} else {

						    			echo '<input type="file" accept=".pem" name="wccd-certificate" class="wccd-certificate">';
						    			echo '<p class="description">' . esc_html(__('Carica il certificato (.pem) necessario alla connessione con Carta del docente', 'wccd')) . '</p>';
			
				    				}
				    			echo '</td>';
				    		echo '</tr>';

				    		/*Password utilizzata per la creazione del certificato*/
				    		echo '<tr>';
				    			echo '<th scope="row">' . esc_html(__('Password', 'wc18')) . '</th>';
				    			echo '<td>';
			    					echo '<input type="password" name="wccd-password" placeholder="**********" value="' . $passphrase . '" required>';
					    			echo '<p class="description">' . esc_html(__('La password utilizzata per la generazione del certificato', 'wccd')) . '</p>';	

							    	wp_nonce_field('wccd-upload-certificate', 'wccd-certificate-nonce');
							    	echo '<input type="hidden" name="wccd-certificate-hidden" value="1">';
							    	echo '<input type="submit" class="button-primary wccd-button" value="' . esc_html('Salva certificato', 'wccd') . '">';
				    			echo '</td>';
				    		echo '</tr>';

			    		echo '</table>';
			    	echo '</form>';
	
				    /*Se il certificato non è presente vengono mostrati gli strumentui per generarlo*/
		    		if(!self::get_the_file('.pem')) {
				
			    		/*Genera richiesta certificato .der*/
			    		echo '<h3>' . esc_html(__('Richiedi un certificato', 'wccd')) . '</h3>';
		    			echo '<p class="description">' . esc_html(__('Con questo strumento puoi generare un file .der necessario per richiedere il tuo certificato su Carta del docente.', 'wccd')) . '</p>';

	    				echo '<form id="generate-certificate-request" method="post" class="one-of" enctype="multipart/form-data" action="">';
							echo '<table class="form-table wccd-table">';
					    		echo '<tr>';
					    			echo '<th scope="row">' . esc_html(__('Stato', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="text" name="countryName" placeholder="IT" required>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Provincia', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="text" name="stateOrProvinceName" placeholder="Es. Milano" required>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Località', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="text" name="localityName" placeholder="Es. Legnano" required>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Nome azienda', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="text" name="organizationName" placeholder="Es. Taldeitali srl" required>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Reparto azienda', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="text" name="organizationalUnitName" placeholder="Es. Vendite" required>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Nome', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="text" name="commonName" placeholder="Es. Franco Bianchi" required>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Email', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="email" name="emailAddress" placeholder="Es. franco.bianchi@taldeitali.it" required>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row">' . esc_html(__('Password', 'wccd')) . '</th>';
					    			echo '<td>';
				    					echo '<input type="password" name="wccd-password" placeholder="**********" required>';
					    			echo '</td>';
					    		echo '</tr>';

				    			echo '<th scope="row"></th>';
					    			echo '<td>';
					    			echo '<input type="hidden" name="generate-der-hidden" value="1">';
				    				echo '<input type="submit" name="generate-der" class="button-primary wccd-button generate-der" value="' . __('Scarica file .der', 'wccd') . '">';
					    			echo '</td>';
					    		echo '</tr>';

				    		echo '</table>';
	    				echo '</form>';


			    		/*Genera certificato .pem*/
			    		echo '<h3>' . esc_html(__('Crea il tuo certificato', 'wccd')) . '</h3>';
		    			echo '<p class="description">' . esc_html(__('Con questo ultimo passaggio, potrai iniziare a ricevere pagamenti attraverso buoni del docente.', 'wccd')) . '</p>';

						echo '<form name="wccd-generate-certificate" class="wccd-generate-certificate" method="post" enctype="multipart/form-data" action="">';
					    	echo '<table class="form-table wccd-table">';

					    		/*Carica certificato*/
					    		echo '<tr>';
					    			echo '<th scope="row">' . esc_html(__('Genera certificato', 'wccd')) . '</th>';
					    			echo '<td>';
					    				
						    			echo '<input type="file" accept=".cer" name="wccd-cert" class="wccd-cert">';
						    			echo '<p class="description">' . esc_html(__('Carica il file .cer ottenuto da Carta del docente per procedere', 'wccd')) . '</p>';
								    	
								    	wp_nonce_field('wccd-generate-certificate', 'wccd-gen-certificate-nonce');
								    	echo '<input type="hidden" name="wccd-gen-certificate-hidden" value="1">';
								    	echo '<input type="submit" class="button-primary wccd-button" value="' . esc_html('Genera certificato', 'wccd') . '">';

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
						    		echo '<input type="hidden" name="wccd-tot-cats" class="wccd-tot-cats" value="' . ($categories ? count($categories) : '') . '">';
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

		if(isset($_POST['premium-key-sent']) && wp_verify_nonce($_POST['wccd-premium-key-nonce'], 'wccd-premium-key')) {

			/*Salvataggio Premium Key*/
			$premium_key = isset($_POST['wccd-premium-key']) ? sanitize_text_field($_POST['wccd-premium-key']) : '';
			update_option('wccd-premium-key', $premium_key);
		
		}

		if(isset($_POST['wccd-gen-certificate-hidden']) && wp_verify_nonce($_POST['wccd-gen-certificate-nonce'], 'wccd-generate-certificate')) {

			/*Salvataggio file .cer*/
			if(isset($_FILES['wccd-cert'])) {
				$info = pathinfo($_FILES['wccd-cert']['name']);
				$name = sanitize_file_name($info['basename']);
				if($info) {
					if($info['extension'] === 'cer') {
						move_uploaded_file($_FILES['wccd-cert']['tmp_name'], WCCD_PRIVATE . $name);	
									
						/*Conversione da .cer a .pem*/
	                    $certificateCAcer = WCCD_PRIVATE . $name;
	                    $certificateCAcerContent = file_get_contents($certificateCAcer);
	                    $certificateCApemContent =  '-----BEGIN CERTIFICATE-----'.PHP_EOL
	                        .chunk_split(base64_encode($certificateCAcerContent), 64, PHP_EOL)
	                        .'-----END CERTIFICATE-----'.PHP_EOL;
	                    $certificateCApem = WCCD_PRIVATE . 'files/wccd-cert.pem';
	                    file_put_contents($certificateCApem, $certificateCApemContent); 
	                    
	                    /*Preparo i file necessari*/
	                    $pem = openssl_x509_read(file_get_contents(WCCD_PRIVATE . 'files/wccd-cert.pem'));
	                    $get_key = file_get_contents(WCCD_PRIVATE . 'files/key.der');

	                    /*Richiamo la passphrase dal db*/
	                    $wccd_password = base64_decode(get_option('wccd-password'));

	                    $key = array($get_key, $wccd_password);

	                    openssl_pkcs12_export_to_file($pem, WCCD_PRIVATE . 'files/wccd-cert.p12', $key, $wccd_password);

	                    /*Preparo i file necessari*/	                    
	                    openssl_pkcs12_read(file_get_contents(WCCD_PRIVATE . 'files/wccd-cert.p12'), $p12, $wccd_password);

	                    /*Creo il certificato*/
	                    file_put_contents(WCCD_PRIVATE . 'wccd-certificate.pem', $p12['cert'] . $key[0]);

					} else {
						add_action('admin_notices', array($this, 'not_valid_certificate'));
					}					
				}
			}
		}

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

			/*Password*/
            $wccd_password = isset($_POST['wccd-password']) ? sanitize_text_field($_POST['wccd-password']) : '';

            /*Salvo passw nel db*/
            if($wccd_password) {
            	update_option('wccd-password', base64_encode($wccd_password));
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