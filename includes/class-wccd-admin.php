<?php
/**
 * Pagina opzioni e gestione certificati
 *
 * @author ilGhera
 * @package wc-carta-docente/includes
 * @since 1.3.0
 */

/**
 * WCCD_Admin class
 */
class WCCD_Admin {

	/**
	 * The sandbox option
	 *
	 * @var bool
	 */
	private $sandbox;

	/**
	 * The constructor
	 *
	 * @return void
	 */
	public function __construct() {

		$this->sandbox = get_option( 'wccd-sandbox' );

		add_action( 'admin_init', array( $this, 'wccd_save_settings' ) );
		add_action( 'admin_init', array( $this, 'generate_cert_request' ) );
		add_action( 'admin_menu', array( $this, 'register_options_page' ) );
		add_action( 'wp_ajax_wccd-delete-certificate', array( $this, 'delete_certificate_callback' ), 1 );
		add_action( 'wp_ajax_wccd-add-cat', array( $this, 'add_cat_callback' ) );
		add_action( 'wp_ajax_wccd-sandbox', array( $this, 'sandbox_callback' ) );
	}


	/**
	 * Registra la pagina opzioni del plugin
	 *
	 * @return void
	 */
	public function register_options_page() {

		add_submenu_page( 'woocommerce', __( 'WooCommerce Carta docente - Impostazioni', 'wccd' ), __( 'WC Carta Docente', 'wccd' ), 'manage_options', 'wccd-settings', array( $this, 'wccd_settings' ) );

	}


	/**
	 * Verifica la presenza di un file per estenzione
	 *
	 * @param string $ext l,estensione del file da cercare.
	 *
	 * @return string l'url file
	 */
	public static function get_the_file( $ext ) {

		$files = array();

		foreach ( glob( WCCD_PRIVATE . '*' . $ext ) as $file ) {
			$files[] = $file;
		}

		$output = empty( $files ) ? false : $files[0];

		return $output;

	}


	/**
	 * Cancella il certificato
	 *
	 * @return void
	 */
	public function delete_certificate_callback() {

		if ( isset( $_POST['wccd-delete'], $_POST['delete-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['delete-nonce'] ) ), 'wccd-del-cert-nonce' ) ) {

			$cert = isset( $_POST['cert'] ) ? sanitize_text_field( wp_unslash( $_POST['cert'] ) ) : '';

			if ( $cert ) {

				unlink( WCCD_PRIVATE . $cert );

			}
		}

		exit;

	}


	/**
	 * Restituisce il nome esatto del bene Carta del Docente partendo dallo slug
	 *
	 * @param  array  $beni      l'elenco dei beni di carta del docente.
	 * @param  string $bene_slug lo slug del bene.
	 *
	 * @return string
	 */
	public function get_bene_lable( $beni, $bene_slug ) {

		foreach ( $beni as $bene ) {

			if ( sanitize_title( $bene ) === $bene_slug ) {

				return $bene;

			}
		}

	}


	/**
	 * Categoria per la verifica in fase di checkout
	 *
	 * @param  int   $n             il numero dell'elemento aggiunto.
	 * @param  array $data          bene e categoria come chiave e velore.
	 * @param  array $exclude_beni  buoni già abbinati a categorie WC (al momento non utilizzato).
	 *
	 * @return mixed
	 */
	public function setup_cat( $n, $data = null, $exclude_beni = null ) {

		echo '<li class="setup-cat cat-' . esc_attr( $n ) . '">';

			/*L'elenco dei beni dei vari ambiti previsti dalla piattaforma*/
			$beni_index = array(
				'Libri e testi (anche in formato digitale)',
				'Hardware e software',
				'Formazione e aggiornamento',
				'Teatro',
				'Cinema',
				'Mostre ed eventi culturali',
				'Spettacoli dal vivo',
				'Musei',
			);

			$beni       = array_map( 'sanitize_title', $beni_index );
			$terms      = get_terms( 'product_cat' );
			$bene_value = is_array( $data ) ? key( $data ) : '';
			$term_value = $bene_value ? $data[ $bene_value ] : '';

			echo '<select name="wccd-beni-' . esc_attr( $n ) . '" class="wccd-field beni">';
				echo '<option value="">Bene carta docente</option>';

			foreach ( $beni as $bene ) {

				echo '<option value="' . esc_attr( $bene ) . '"' . ( $bene === $bene_value ? ' selected="selected"' : '' ) . '>' . esc_html( $this->get_bene_lable( $beni_index, $bene ) ) . '</option>';

			}
			echo '</select>';

			echo '<select name="wccd-categories-' . esc_attr( $n ) . '" class="wccd-field categories">';
				echo '<option value="">Categoria WooCommerce</option>';

			foreach ( $terms as $term ) {
				echo '<option value="' . esc_attr( $term->term_id ) . '"' . ( intval( $term_value ) === $term->term_id ? ' selected="selected"' : '' ) . '>' . esc_html( $term->name ) . '</option>';
			}
			echo '</select>';

			if ( 1 === intval( $n ) ) {

				echo '<div class="add-cat-container">';
					echo '<img class="add-cat" src="' . esc_url( WCCD_URI . 'images/add-cat.png' ) . '">';
					echo '<img class="add-cat-hover wccd" src="' . esc_url( WCCD_URI . 'images/add-cat-hover.png' ) . '">';
				echo '</div>';

			} else {

				echo '<div class="remove-cat-container">';
					echo '<img class="remove-cat" src="' . esc_url( WCCD_URI . 'images/remove-cat.png' ) . '">';
					echo '<img class="remove-cat-hover" src="' . esc_url( WCCD_URI . 'images/remove-cat-hover.png' ) . '">';
				echo '</div>';

			}

			echo '</li>';
	}


	/**
	 * Aggiunge una nuova categoria per la verifica in fase di checkout
	 *
	 * @return void
	 */
	public function add_cat_callback() {

		if ( isset( $_POST['add-cat-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['add-cat-nonce'] ) ), 'wccd-add-cat-nonce' ) ) {

			$number       = isset( $_POST['number'] ) ? sanitize_text_field( wp_unslash( $_POST['number'] ) ) : '';
			$exclude_beni = isset( $_POST['exclude-beni'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude-beni'] ) ) : '';

			if ( $number ) {

				$this->setup_cat( $number, null, $exclude_beni );

			}
		}

		exit;
	}


	/**
	 * Trasforma il contenuto di un certificato .pem in .der
	 *
	 * @param  string $pem_data il certificato .pem.
	 *
	 * @return string
	 */
	public function pem2der( $pem_data ) {

		$begin    = '-----BEGIN CERTIFICATE REQUEST-----';
		$end      = '-----END CERTIFICATE REQUEST-----';
		$pem_data = substr( $pem_data, strpos( $pem_data, $begin ) + strlen( $begin ) );
		$pem_data = substr( $pem_data, 0, strpos( $pem_data, $end ) );
		$der      = base64_decode( $pem_data );

		return $der;
	}


	/**
	 * Download della richiesta di certificato da utilizzare sul portale Carta del Docente
	 * Se non presenti, genera la chiave e la richiesta di certificato .der,
	 *
	 * @return void
	 */
	public function generate_cert_request() {

		if ( isset( $_POST['wccd-generate-der-hidden'], $_POST['wccd-generate-der-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wccd-generate-der-nonce'] ) ), 'wccd-generate-der' ) ) {

			/*Crea il file .der*/
			$country_name             = isset( $_POST['countryName'] ) ? sanitize_text_field( wp_unslash( $_POST['countryName'] ) ) : '';
			$state_or_provice_name    = isset( $_POST['stateOrProvinceName'] ) ? sanitize_text_field( wp_unslash( $_POST['stateOrProvinceName'] ) ) : '';
			$locality_name            = isset( $_POST['localityName'] ) ? sanitize_text_field( wp_unslash( $_POST['localityName'] ) ) : '';
			$organization_name        = isset( $_POST['organizationName'] ) ? sanitize_text_field( wp_unslash( $_POST['organizationName'] ) ) : '';
			$organizational_unit_name = isset( $_POST['organizationalUnitName'] ) ? sanitize_text_field( wp_unslash( $_POST['organizationalUnitName'] ) ) : '';
			$common_name              = isset( $_POST['commonName'] ) ? sanitize_text_field( wp_unslash( $_POST['commonName'] ) ) : '';
			$email_address            = isset( $_POST['emailAddress'] ) ? sanitize_text_field( wp_unslash( $_POST['emailAddress'] ) ) : '';
			$wccd_password            = isset( $_POST['wccd-password'] ) ? sanitize_text_field( wp_unslash( $_POST['wccd-password'] ) ) : '';

			/*Salvo passw nel db*/
			if ( $wccd_password ) {
				update_option( 'wccd-password', base64_encode( $wccd_password ) );
			}

			$dn = array(
				'countryName'            => $country_name,
				'stateOrProvinceName'    => $state_or_provice_name,
				'localityName'           => $locality_name,
				'organizationName'       => $organization_name,
				'organizationalUnitName' => $organizational_unit_name,
				'commonName'             => $common_name,
				'emailAddress'           => $email_address,
			);

			/*Genera la private key*/
			$privkey = openssl_pkey_new(
				array(
					'private_key_bits' => 2048,
					'private_key_type' => OPENSSL_KEYTYPE_RSA,
				)
			);

			/*Genera ed esporta la richiesta di certificato .pem*/
			$csr = openssl_csr_new( $dn, $privkey, array( 'digest_alg' => 'sha256' ) );
			openssl_csr_export_to_file( $csr, WCCD_PRIVATE . 'files/certificate-request.pem' );

			/*Trasforma la richiesta di certificato in .der*/
			$csr_der = $this->pem2der( file_get_contents( WCCD_PRIVATE . 'files/certificate-request.pem' ) );

			/*Preparo il backup*/
			$bu_folder = WCCD_PRIVATE . 'files/backups/';

			error_log( count( glob( $bu_folder . '*', GLOB_ONLYDIR ) ) + 1 );

			$bu_new_folder_name   = count( glob( $bu_folder . '*', GLOB_ONLYDIR ) ) + 1;
			$bu_new_folder_create = wp_mkdir_p( trailingslashit( $bu_folder . $bu_new_folder_name ) );

			/*Salvo file di backup*/
			if ( $bu_new_folder_create ) {

				/*Esporta la richiesta di certificato .der*/
				file_put_contents( WCCD_PRIVATE . 'files/backups/' . $bu_new_folder_name . '/certificate-request.der', $csr_der );

				/*Esporta la private key*/
				openssl_pkey_export_to_file( $privkey, WCCD_PRIVATE . 'files/backups/' . $bu_new_folder_name . '/key.der' );

			}

			/*Esporta la richiesta di certificato .der*/
			file_put_contents( WCCD_PRIVATE . 'files/certificate-request.der', $csr_der );

			/*Esporta la private key*/
			openssl_pkey_export_to_file( $privkey, WCCD_PRIVATE . 'files/key.der' );

			/*Download file .der*/
			$cert_req_url = WCCD_PRIVATE . 'files/certificate-request.der';

			if ( $cert_req_url ) {
				header( 'Content-Description: File Transfer' );
				header( 'Content-Type: application/octet-stream' );
				header( 'Content-Transfer-Encoding: binary' );
				header( 'Content-disposition: attachment; filename="' . basename( $cert_req_url ) . '"' );
				header( 'Expires: 0' );
				header( 'Cache-Control: must-revalidate' );
				header( 'Pragma: public' );

				readfile( $cert_req_url );

				exit;
			}
		}
	}


	/**
	 * Attivazione certificato
	 *
	 * @return string
	 */
	public function wccd_cert_activation() {

		$soap_client = new WCCD_Soap_Client( '11aa22bb', '' );

		try {

			$operation = $soap_client->check( 1 );
			return 'ok';

		} catch ( Exception $e ) {

			$notice = isset( $e->detail->FaultVoucher->exceptionMessage ) ? $e->detail->FaultVoucher->exceptionMessage : $e->faultstring;
			error_log( 'Error wccd_cert_activation: ' . print_r( $e, true ) );

			return $notice;

		}
	}


	/**
	 * Funzionalita Sandbox
	 *
	 * @return void
	 */
	public function sandbox_callback() {

		if ( isset( $_POST['sandbox'], $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wccd-sandbox' ) ) {

			$this->sandbox = sanitize_text_field( wp_unslash( $_POST['sandbox'] ) );

			update_option( 'wccd-sandbox', $this->sandbox );
			update_option( 'wccd-cert-activation', $this->sandbox );

		}

		exit();

	}


	/**
	 * Pagina opzioni plugin
	 *
	 * @return void
	 */
	public function wccd_settings() {

		/*Recupero le opzioni salvate nel db*/
		$premium_key               = get_option( 'wccd-premium-key' );
		$passphrase                = base64_decode( get_option( 'wccd-password' ) );
		$categories                = get_option( 'wccd-categories' );
		$tot_cats                  = $categories ? count( $categories ) : 0;
		$wccd_coupon               = get_option( 'wccd-coupon' );
		$wccd_image                = get_option( 'wccd-image' );
		$wccd_items_check          = get_option( 'wccd-items-check' );
		$wccd_orders_on_hold       = get_option( 'wccd-orders-on-hold' );
		$wccd_email_subject        = get_option( 'wccd-email-subject' );
		$wccd_email_heading        = get_option( 'wccd-email-heading' );
		$wccd_email_order_received = get_option( 'wccd-email-order-received' );
		$wccd_email_order_failed   = get_option( 'wccd-email-order-failed' );

		echo '<div class="wrap">';
			echo '<div class="wrap-left">';
				echo '<h1>WooCommerce Carta Docente - ' . esc_html( __( 'Impostazioni', 'wccd' ) ) . '</h1>';

				/*Premium key form*/
				echo '<form method="post" action="">';
					echo '<table class="form-table wccd-table">';
						echo '<th scope="row">' . esc_html__( 'Premium Key', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" class="regular-text code" name="wccd-premium-key" id="wccd-premium-key" placeholder="' . esc_attr__( 'Inserisci la tua Premium Key', 'wccd' ) . '" value="' . esc_attr( $premium_key ) . '" />';
							echo '<p class="description">' . esc_html__( 'Aggiungi la tua Premium Key e mantieni aggiornato <strong>Woocommerce Carta Docente - Premium</strong>.', 'wccd' ) . '</p>';

							wp_nonce_field( 'wccd-premium-key', 'wccd-premium-key-nonce' );

							echo '<input type="hidden" name="premium-key-sent" value="1" />';
							echo '<input type="submit" class="button button-primary wccd-button"" value="' . esc_html__( 'Salva ', 'wccd' ) . '" />';
						echo '</td>';
					echo '</table>';
				echo '</form>';

				/*Tabs*/
				echo '<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"></div>';
				echo '<h2 id="wccd-admin-menu" class="nav-tab-wrapper woo-nav-tab-wrapper">';
					echo '<a href="#" data-link="wccd-certificate" class="nav-tab nav-tab-active" onclick="return false;">' . esc_html( __( 'Certificato', 'wccd' ) ) . '</a>';
					echo '<a href="#" data-link="wccd-options" class="nav-tab" onclick="return false;">' . esc_html__( 'Opzioni', 'wccd' ) . '</a>';
				echo '</h2>';

				/*Certificate*/
				echo '<div id="wccd-certificate" class="wccd-admin" style="display: block;">';

					/*Carica certificato .pem*/
					echo '<h3>' . esc_html__( 'Carica il tuo certificato', 'wccd' ) . '</h3>';
					echo '<p class="description">' . esc_html__( 'Se sei già in posseso di un certificato non devi fare altro che caricarlo con relativa password, nient\'altro.', 'wccd' ) . '</p>';

					echo '<form name="wccd-upload-certificate" class="wccd-upload-certificate one-of" method="post" enctype="multipart/form-data" action="">';
						echo '<table class="form-table wccd-table">';

							/*Carica certificato*/
							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Carica certificato', 'wccd' ) . '</th>';
								echo '<td>';
		if ( $file = self::get_the_file( '.pem' ) ) {

			$activation = $this->wccd_cert_activation();

			if ( 'ok' === $activation ) {

				echo '<span class="cert-loaded">' . esc_html( basename( $file ) ) . '</span>';
				echo '<a class="button delete wccd-delete-certificate">' . esc_html__( 'Elimina', 'wccd' ) . '</a>';
				echo '<p class="description">' . esc_html__( 'File caricato e attivato correttamente.', 'wccd' ) . '</p>';

				update_option( 'wccd-cert-activation', 1 );

			} else {

				echo '<span class="cert-loaded error">' . esc_html( basename( $file ) ) . '</span>';
				echo '<a class="button delete wccd-delete-certificate">' . esc_html__( 'Elimina', 'wccd' ) . '</a>';

				/* Translators: the error message */
				echo '<p class="description">' . sprintf( esc_html__( 'L\'attivazione del certificato ha restituito il seguente errore: %s', 'wccd' ), esc_html( $activation ) ) . '</p>';

				delete_option( 'wccd-cert-activation' );

			}
		} else {

			echo '<input type="file" accept=".pem" name="wccd-certificate" class="wccd-certificate">';
			echo '<p class="description">' . esc_html__( 'Carica il certificato (.pem) necessario alla connessione con Carta del docente', 'wccd' ) . '</p>';

		}

								echo '</td>';
							echo '</tr>';

							/*Password utilizzata per la creazione del certificato*/
							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Password', 'wccd' ) . '</th>';
								echo '<td>';
									echo '<input type="password" name="wccd-password" placeholder="**********" value="' . esc_attr( $passphrase ) . '" required>';
									echo '<p class="description">' . esc_html__( 'La password utilizzata per la generazione del certificato', 'wccd' ) . '</p>';

									wp_nonce_field( 'wccd-upload-certificate', 'wccd-certificate-nonce' );

									echo '<input type="hidden" name="wccd-certificate-hidden" value="1">';
									echo '<input type="submit" class="button-primary wccd-button" value="' . esc_html__( 'Salva certificato', 'wccd' ) . '">';
								echo '</td>';
							echo '</tr>';

						echo '</table>';
					echo '</form>';

		/*Se il certificato non è presente vengono mostrati gli strumentui per generarlo*/
		if ( ! self::get_the_file( '.pem' ) ) {

			/*Genera richiesta certificato .der*/
			echo '<h3>' . esc_html__( 'Richiedi un certificato', 'wccd' ) . '</h3>';
			echo '<p class="description">' . esc_html__( 'Con questo strumento puoi generare un file .der necessario per richiedere il tuo certificato su Carta del docente.', 'wccd' ) . '</p>';

			echo '<form id="generate-certificate-request" method="post" class="one-of" enctype="multipart/form-data" action="">';
				echo '<table class="form-table wccd-table">';
					echo '<tr>';
						echo '<th scope="row">' . esc_html__( 'Stato', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="countryName" placeholder="IT" required>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Provincia', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="stateOrProvinceName" placeholder="Es. Milano" required>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Località', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="localityName" placeholder="Es. Legnano" required>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Nome azienda', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="organizationName" placeholder="Es. Taldeitali srl" required>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Reparto azienda', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="organizationalUnitName" placeholder="Es. Vendite" required>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Nome', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="commonName" placeholder="Es. Franco Bianchi" required>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Email', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="email" name="emailAddress" placeholder="Es. franco.bianchi@taldeitali.it" required>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Password', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="password" name="wccd-password" placeholder="**********" required>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row"></th>';
						echo '<td>';
						wp_nonce_field( 'wccd-generate-der', 'wccd-generate-der-nonce' );
						echo '<input type="hidden" name="wccd-generate-der-hidden" value="1">';
						echo '<input type="submit" name="generate-der" class="button-primary wccd-button generate-der" value="' . esc_attr__( 'Scarica file .der', 'wccd' ) . '">';
						echo '</td>';
					echo '</tr>';

				echo '</table>';
			echo '</form>';

			/*Genera certificato .pem*/
			echo '<h3>' . esc_html( __( 'Crea il tuo certificato', 'wccd' ) ) . '</h3>';
			echo '<p class="description">' . esc_html__( 'Con questo ultimo passaggio, potrai iniziare a ricevere pagamenti attraverso buoni del docente.', 'wccd' ) . '</p>';

			echo '<form name="wccd-generate-certificate" class="wccd-generate-certificate one-of" method="post" enctype="multipart/form-data" action="">';
				echo '<table class="form-table wccd-table">';

					/*Carica certificato*/
					echo '<tr>';
						echo '<th scope="row">' . esc_html__( 'Genera certificato', 'wccd' ) . '</th>';
						echo '<td>';

							echo '<input type="file" accept=".cer" name="wccd-cert" class="wccd-cert">';
							echo '<p class="description">' . esc_html__( 'Carica il file .cer ottenuto da Carta del docente per procedere', 'wccd' ) . '</p>';

							wp_nonce_field( 'wccd-generate-certificate', 'wccd-gen-certificate-nonce' );

							echo '<input type="hidden" name="wccd-gen-certificate-hidden" value="1">';
							echo '<input type="submit" class="button-primary wccd-button" value="' . esc_html__( 'Genera certificato', 'wccd' ) . '">';

						echo '</td>';
					echo '</tr>';

				echo '</table>';
			echo '</form>';

		}

				echo '</div>';

				/*Modalità Sandbox*/
				echo '<div id="wccd-sandbox-option" class="wccd-admin" style="display: block;">';
					echo '<h3>' . esc_html__( 'Modalità Sandbox', 'wccd' ) . '</h3>';
				echo '<p class="description">';
					/* Translators: the email address */
					printf( wp_kses_post( __( 'Attiva questa funzionalità per testare buoni Carta del Docente in un ambiente di prova.<br>Richiedi i buoni test scrivendo a <a href="%s">docenti@sogei.it</a>', 'wccd' ) ), 'mailto:docenti@sogei.it' );
				echo '</p>';

					echo '<form name="wccd-sandbox" class="wccd-sandbox" method="post" enctype="multipart/form-data" action="">';
						echo '<table class="form-table wccd-table">';

							/*Carica certificato*/
							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Sandbox', 'wccd' ) . '</th>';
								echo '<td class="wccd-sandbox-field">';
									echo '<input type="checkbox" name="wccd-sandbox" class="wccd-sandbox"' . ( $this->sandbox ? ' checked="checked"' : null ) . '>';
									echo '<p class="description">' . esc_html__( 'Attiva modalità Sandbox', 'wccd' ) . '</p>';

									wp_nonce_field( 'wccd-sandbox', 'wccd-sandbox-nonce' );

									echo '<input type="hidden" name="wccd-sandbox-hidden" value="1">';

								echo '</td>';
							echo '</tr>';

						echo '</table>';
					echo '</form>';
				echo '</div>';

				/*Options*/
				echo '<div id="wccd-options" class="wccd-admin">';

					echo '<form name="wccd-options" class="wccd-form wccd-options" method="post" enctype="multipart/form-data" action="">';
						echo '<table class="form-table">';

							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Categorie', 'wccd' ) . '</th>';
								echo '<td>';

									echo '<ul  class="categories-container">';

		if ( $categories ) {

			for ( $i = 1; $i <= $tot_cats; $i++ ) {

				$this->setup_cat( $i, $categories[ $i - 1 ] );

			}
		} else {

			$this->setup_cat( 1 );

		}

									echo '</ul>';
									echo '<input type="hidden" name="wccd-tot-cats" class="wccd-tot-cats" value="' . ( is_array( $categories ) ? esc_attr( count( $categories ) ) : 1 ) . '">';
									echo '<p class="description">' . esc_html__( 'Seleziona le categorie di prodotti corrispondenti ai beni acquistabili.', 'wccd' ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Conversione in coupon', 'wccd' ) . '</th>';
								echo '<td>';
									echo '<input type="checkbox" name="wccd-coupon" value="1"' . ( 1 === intval( $wccd_coupon ) ? ' checked="checked"' : '' ) . '>';
									echo '<p class="description">' . wp_kses_post( __( 'Nel caso in cui il buono <i>Carta del Docente</i> inserito sia inferiore al totale a carrello, viene convertito in <i>Codice promozionale</i> ed applicato all\'ordine.', 'wccd' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Utilizzo immagine', 'wccd' ) . '</th>';
								echo '<td>';
									echo '<input type="checkbox" name="wccd-image" value="1"' . ( 1 === intval( $wccd_image ) ? ' checked="checked"' : '' ) . '>';
									echo '<p class="description">' . wp_kses_post( __( 'Mostra il logo <i>Carta del Docente</i> nella pagine di checkout.', 'wccd' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Controllo prodotti', 'wccd' ) . '</th>';
								echo '<td>';
										echo '<input type="checkbox" name="wccd-items-check" value="1"' . ( 1 === intval( $wccd_items_check ) ? ' checked="checked"' : '' ) . '>';
									echo '<p class="description">' . wp_kses_post( __( 'Mostra il metodo di pagamento solo se il/ i prodotti a carrello sono acquistabili con buoni <i>Carta del Docente</i>.<br>Più prodotti dovranno prevedere l\'uso di buoni dello stesso ambito di utilizzo.', 'wccd' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccd-orders-on-hold">';
								echo '<th scope="row">' . esc_html__( 'Ordini in sospeso', 'wccd' ) . '</th>';
								echo '<td>';
										echo '<input type="checkbox" name="wccd-orders-on-hold" value="1"' . ( 1 === intval( $wccd_orders_on_hold ) ? ' checked="checked"' : '' ) . '>';
									echo '<p class="description">' . wp_kses_post( __( 'I buoni Carta del Docente verranno validati con il completamento manuale degli ordini.', 'wccd' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccd-email-order-received wccd-email-details">';
								echo '<th scope="row">' . esc_html__( 'Ordine ricevuto', 'wccd' ) . '</th>';
								echo '<td>';
									$default_order_received_message = __( 'L\'ordine verrà completato manualmente nei prossimi giorni e, contestualmente, verrà validato il buono Carta del Docente inserito. Riceverai una notifica email di conferma, grazie!', 'wccd' );
									echo '<textarea cols="6" rows="6" class="regular-text" name="wccd-email-order-received" placeholder="' . esc_html( $default_order_received_message ) . '" value="' . esc_html( $wccd_email_order_received ) . '">' . esc_html( $wccd_email_order_received ) . '</textarea>';
									echo '<p class="description">';
										echo wp_kses_post( __( 'Messaggio della mail inviata all\'utente al ricevimento dell\'ordine.', 'wccd' ) );
									echo '</p>';
									echo '<div class="wccd-divider"></div>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccd-email-subject wccd-email-details">';
								echo '<th scope="row">' . esc_html__( 'Oggetto email', 'wccd' ) . '</th>';
								echo '<td>';
										echo '<input type="text" class="regular-text" name="wccd-email-subject" placeholder="' . esc_attr__( 'Ordine fallito', 'wccd' ) . '" value="' . esc_attr( $wccd_email_subject ) . '">';
									echo '<p class="description">' . wp_kses_post( __( 'Oggetto della mail inviata all\'utente nel caso in cui la validazione del buono non sia andata a buon fine.', 'wccd' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccd-email-heading wccd-email-details">';
								echo '<th scope="row">' . esc_html__( 'Intestazione email', 'wccd' ) . '</th>';
								echo '<td>';
										echo '<input type="text" class="regular-text" name="wccd-email-heading" placeholder="' . esc_attr__( 'Ordine fallito', 'wccd' ) . '" value="' . esc_attr( $wccd_email_heading ) . '">';
									echo '<p class="description">' . wp_kses_post( __( 'Intestazione della mail inviata all\'utente nel caso in cui la validazione del buono non sia andata a buon fine.', 'wccd' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccd-email-order-failed wccd-email-details">';
								echo '<th scope="row">' . esc_html__( 'Ordine fallito', 'wccd' ) . '</th>';
								echo '<td>';
										$default_order_failed_message = __( 'La validazone del buono Carta del Docente ha restituito un errore e non è stato possibile completare l\'ordine, effettua il pagamento a <a href="[checkout-url]">questo indirizzo</a>.' );
										echo '<textarea cols="6" rows="6" class="regular-text" name="wccd-email-order-failed" placeholder="' . esc_html( $default_order_failed_message ) . '" value="' . esc_html( $wccd_email_order_failed ) . '">' . esc_html( $wccd_email_order_failed ) . '</textarea>';
										echo '<p class="description">';
											echo '<span class="shortcodes">';
												echo '<code>[checkout-url]</code>';
											echo '</span>';
											echo wp_kses_post( __( 'Messaggio della mail inviata all\'utente nel caso in cui la validazione del buono non sia andata a buon fine.', 'wccd' ) );
										echo '</p>';
								echo '</td>';
							echo '</tr>';

						echo '</table>';

						wp_nonce_field( 'wccd-save-settings', 'wccd-settings-nonce' );

						echo '<input type="hidden" name="wccd-settings-hidden" value="1">';
						echo '<input type="submit" class="button-primary" value="' . esc_html__( 'Salva impostazioni', 'wccd' ) . '">';
					echo '</form>';
				echo '</div>';

			echo '</div>';

			echo '<div class="wrap-right">';
				echo '<iframe width="300" height="1300" scrolling="no" src="http://www.ilghera.com/images/wccd-premium-iframe.html"></iframe>';
			echo '</div>';
			echo '<div class="clear"></div>';

		echo '</div>';

	}


	/**
	 * Mostra un mesaggio d'errore nel caso in cui il certificato non isa valido
	 *
	 * @return void
	 */
	public function not_valid_certificate() {

		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'ATTENZIONE! Il file caricato non sembra essere un certificato valido.', 'wccd' ); ?></p>
		</div>
		<?php

	}


	/**
	 * Salvataggio delle impostazioni dell'utente
	 *
	 * @return void
	 */
	public function wccd_save_settings() {

		if ( isset( $_POST['premium-key-sent'], $_POST['wccd-premium-key-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wccd-premium-key-nonce'] ) ), 'wccd-premium-key' ) ) {

			/*Salvataggio Premium Key*/
			$premium_key = isset( $_POST['wccd-premium-key'] ) ? sanitize_text_field( wp_unslash( $_POST['wccd-premium-key'] ) ) : '';

			update_option( 'wccd-premium-key', $premium_key );

		}

		if ( isset( $_POST['wccd-gen-certificate-hidden'], $_POST['wccd-gen-certificate-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wccd-gen-certificate-nonce'] ) ), 'wccd-generate-certificate' ) ) {

			/*Salvataggio file .cer*/
			if ( isset( $_FILES['wccd-cert']['name'] ) ) {

				$file_name = sanitize_text_field( wp_unslash( $_FILES['wccd-cert']['name'] ) );
				$info      = isset( $_FILES['wccd-cert']['name'] ) ? pathinfo( $file_name ) : null;
				$name      = isset( $info['basename'] ) ? sanitize_file_name( $info['basename'] ) : null;

				if ( $info ) {

					if ( 'cer' === $info['extension'] ) {

						if ( isset( $_FILES['wccd-cert']['tmp_name'] ) ) {

							$tmp_name = sanitize_text_field( wp_unslash( $_FILES['wccd-cert']['tmp_name'] ) );
							move_uploaded_file( $tmp_name, WCCD_PRIVATE . $name );

						}

						/*Conversione da .cer a .pem*/
						$certificate_ca_cer         = WCCD_PRIVATE . $name;
						$certificate_ca_cer_content = file_get_contents( $certificate_ca_cer );
						$certificate_ca_pem_content = '-----BEGIN CERTIFICATE-----' . PHP_EOL
							. chunk_split( base64_encode( $certificate_ca_cer_content ), 64, PHP_EOL )
							. '-----END CERTIFICATE-----' . PHP_EOL;
						$certificate_ca_pem         = WCCD_PRIVATE . 'files/wccd-cert.pem';
						file_put_contents( $certificate_ca_pem, $certificate_ca_pem_content );

						/*Preparo i file necessari*/
						$pem     = openssl_x509_read( file_get_contents( WCCD_PRIVATE . 'files/wccd-cert.pem' ) );
						$get_key = file_get_contents( WCCD_PRIVATE . 'files/key.der' );

						/*Richiamo la passphrase dal db*/
						$wccd_password = base64_decode( get_option( 'wccd-password' ) );

						$key = array( $get_key, $wccd_password );

						openssl_pkcs12_export_to_file( $pem, WCCD_PRIVATE . 'files/wccd-cert.p12', $key, $wccd_password );

						/*Preparo i file necessari*/
						openssl_pkcs12_read( file_get_contents( WCCD_PRIVATE . 'files/wccd-cert.p12' ), $p12, $wccd_password );

						/*Creo il certificato*/
						file_put_contents( WCCD_PRIVATE . 'wccd-certificate.pem', $p12['cert'] . $key[0] );

					} else {
						add_action( 'admin_notices', array( $this, 'not_valid_certificate' ) );
					}
				}
			}
		}

		if ( isset( $_POST['wccd-certificate-hidden'], $_POST['wccd-certificate-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wccd-certificate-nonce'] ) ), 'wccd-upload-certificate' ) ) {

			/*Carica certificato*/
			if ( isset( $_FILES['wccd-certificate'] ) ) {

				$info = isset( $_FILES['wccd-certificate']['name'] ) ? pathinfo( sanitize_text_field( wp_unslash( $_FILES['wccd-certificate']['name'] ) ) ) : null;
				$name = isset( $info['basename'] ) ? sanitize_file_name( $info['basename'] ) : null;

				if ( $info ) {

					if ( 'pem' === $info['extension'] ) {

						if ( isset( $_FILES['wccd-certificate']['tmp_name'] ) ) {

							$tmp_name = sanitize_text_field( wp_unslash( $_FILES['wccd-certificate']['tmp_name'] ) );
							move_uploaded_file( $tmp_name, WCCD_PRIVATE . $name );

						}
					} else {

						add_action( 'admin_notices', array( $this, 'not_valid_certificate' ) );

					}
				}
			}

			/*Password*/
			$wccd_password = isset( $_POST['wccd-password'] ) ? sanitize_text_field( wp_unslash( $_POST['wccd-password'] ) ) : '';

			/*Salvo passw nel db*/
			if ( $wccd_password ) {

				update_option( 'wccd-password', base64_encode( $wccd_password ) );

			}
		}

		if ( isset( $_POST['wccd-settings-hidden'], $_POST['wccd-settings-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wccd-settings-nonce'] ) ), 'wccd-save-settings' ) ) {

			/*Impostazioni categorie per il controllo in fase di checkout*/
			if ( isset( $_POST['wccd-tot-cats'] ) ) {

				$tot = sanitize_text_field( wp_unslash( $_POST['wccd-tot-cats'] ) );
				$tot = $tot ? $tot : 1;

				$wccd_categories = array();

				for ( $i = 1; $i <= $tot; $i++ ) {

					$bene = isset( $_POST[ 'wccd-beni-' . $i ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'wccd-beni-' . $i ] ) ) : '';
					$cat  = isset( $_POST[ 'wccd-categories-' . $i ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'wccd-categories-' . $i ] ) ) : '';

					if ( $bene && $cat ) {

						$wccd_categories[] = array( $bene => $cat );

					}
				}

				update_option( 'wccd-categories', $wccd_categories );
			}

			/*Conversione in coupon*/
			$wccd_coupon = isset( $_POST['wccd-coupon'] ) ? sanitize_text_field( wp_unslash( $_POST['wccd-coupon'] ) ) : '';
			update_option( 'wccd-coupon', $wccd_coupon );

			/*Immagine in pagina di checkout*/
			$wccd_image = isset( $_POST['wccd-image'] ) ? sanitize_text_field( wp_unslash( $_POST['wccd-image'] ) ) : '';
			update_option( 'wccd-image', $wccd_image );

			/*Controllo prodotti a carrello*/
			$wccd_items_check = isset( $_POST['wccd-items-check'] ) ? sanitize_text_field( wp_unslash( $_POST['wccd-items-check'] ) ) : '';
			update_option( 'wccd-items-check', $wccd_items_check );

			/*Ordini in sospeso*/
			$wccd_orders_on_hold = isset( $_POST['wccd-orders-on-hold'] ) ? sanitize_text_field( wp_unslash( $_POST['wccd-orders-on-hold'] ) ) : '';
			update_option( 'wccd-orders-on-hold', $wccd_orders_on_hold );

			/*Messaggio email ordine ricevuto*/
			$wccd_email_order_received = isset( $_POST['wccd-email-order-received'] ) ? wp_kses_post( wp_unslash( $_POST['wccd-email-order-received'] ) ) : '';
			update_option( 'wccd-email-order-received', $wccd_email_order_received );

			/*Oggetto email*/
			$wccd_email_subject = isset( $_POST['wccd-email-subject'] ) ? sanitize_text_field( wp_unslash( $_POST['wccd-email-subject'] ) ) : '';
			update_option( 'wccd-email-subject', $wccd_email_subject );

			/*Intestazione email*/
			$wccd_email_heading = isset( $_POST['wccd-email-heading'] ) ? sanitize_text_field( wp_unslash( $_POST['wccd-email-heading'] ) ) : '';
			update_option( 'wccd-email-heading', $wccd_email_heading );

			/*Messaggio email ordine ricevuto*/
			$wccd_email_order_failed = isset( $_POST['wccd-email-order-failed'] ) ? wp_kses_post( wp_unslash( $_POST['wccd-email-order-failed'] ) ) : '';
			update_option( 'wccd-email-order-failed', $wccd_email_order_failed );

		}
	}

}
new WCCD_Admin();

