<?php
/**
 * Pagina opzioni e gestione certificati
 *
 * @author ilGhera
 * @package wc-carta-docente/includes
 *
 * @since 1.4.0
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
	public function get_bene_label( $beni, $bene_slug ) {

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

				echo '<option value="' . esc_attr( $bene ) . '"' . ( $bene === $bene_value ? ' selected="selected"' : '' ) . '>' . esc_html( $this->get_bene_label( $beni_index, $bene ) ) . '</option>';

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
	 * Pulsante call to action Premium
	 *
	 * @param bool $no_margin aggiunge la classe CSS con true.
	 *
	 * @return string
	 */
	public function get_go_premium( $no_margin = false ) {

		$output      = '<span class="label label-warning premium' . ( $no_margin ? ' no-margin' : null ) . '">';
			$output .= '<a href="https://www.ilghera.com/product/woocommerce-carta-docente-premium" target="_blank">Premium</a>';
		$output     .= '</span>';

		return $output;

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
		$passphrase = base64_decode( get_option( 'wccd-password' ) );
		$categories = get_option( 'wccd-categories' );
		$tot_cats   = $categories ? count( $categories ) : 0;
		$wccd_image = get_option( 'wccd-image' );

		echo '<div class="wrap">';
			echo '<div class="wrap-left">';
				echo '<h1>WooCommerce Carta Docente - ' . esc_html( __( 'Impostazioni', 'wccd' ) ) . '</h1>';

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
			echo '<h3>' . esc_html( __( 'Richiedi un certificato', 'wccd' ) ) . wp_kses_post( $this->get_go_premium() ) . '</h3>';
			echo '<p class="description">' . esc_html__( 'Con questo strumento puoi generare un file .der necessario per richiedere il tuo certificato su Carta del docente.', 'wccd' ) . '</p>';

			echo '<form id="generate-certificate-request" method="post" class="one-of" enctype="multipart/form-data" action="">';
				echo '<table class="form-table wccd-table">';
					echo '<tr>';
						echo '<th scope="row">' . esc_html__( 'Stato', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="countryName" placeholder="IT" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Provincia', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="stateOrProvinceName" placeholder="Es. Milano" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Località', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="localityName" placeholder="Es. Legnano" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Nome azienda', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="organizationName" placeholder="Es. Taldeitali srl" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Reparto azienda', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="organizationalUnitName" placeholder="Es. Vendite" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Nome', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="commonName" placeholder="Es. Franco Bianchi" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Email', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="email" name="emailAddress" placeholder="Es. franco.bianchi@taldeitali.it" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Password', 'wccd' ) . '</th>';
						echo '<td>';
							echo '<input type="password" name="wccd-password" placeholder="**********" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row"></th>';
						echo '<td>';
						echo '<input type="hidden" name="wccd-generate-der-hidden" value="1">';
						echo '<input type="submit" name="generate-der" class="button-primary wccd-button generate-der" value="' . esc_attr__( 'Scarica file .der', 'wccd' ) . '" disabled>';
						echo '</td>';
					echo '</tr>';

				echo '</table>';
			echo '</form>';

			/*Genera certificato .pem*/
			echo '<h3>' . esc_html( __( 'Crea il tuo certificato', 'wccd' ) ) . wp_kses_post( $this->get_go_premium() ) . '</h3>';
			echo '<p class="description">' . esc_html__( 'Con questo ultimo passaggio, potrai iniziare a ricevere pagamenti attraverso buoni del docente.', 'wccd' ) . '</p>';

			echo '<form name="wccd-generate-certificate" class="wccd-generate-certificate one-of" method="post" enctype="multipart/form-data" action="">';
				echo '<table class="form-table wccd-table">';

					/*Carica certificato*/
					echo '<tr>';
						echo '<th scope="row">' . esc_html__( 'Genera certificato', 'wccd' ) . '</th>';
						echo '<td>';

							echo '<input type="file" accept=".cer" name="wccd-cert" class="wccd-cert" disabled>';
							echo '<p class="description">' . esc_html__( 'Carica il file .cer ottenuto da Carta del docente per procedere', 'wccd' ) . '</p>';

							echo '<input type="hidden" name="wccd-gen-certificate-hidden" value="1">';
							echo '<input type="submit" class="button-primary wccd-button" value="' . esc_html__( 'Genera certificato', 'wccd' ) . '" disabled>';

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
								echo '<th scope="row">' . esc_html__( 'Utilizzo immagine', 'wccd' ) . '</th>';
								echo '<td>';
									echo '<input type="checkbox" name="wccd-image" value="1"' . ( 1 === intval( $wccd_image ) ? ' checked="checked"' : '' ) . '>';
									echo '<p class="description">' . wp_kses_post( __( 'Mostra il logo <i>Carta del Docente</i> nella pagine di checkout.', 'wccd' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Controllo prodotti', 'wccd' ) . '</th>';
								echo '<td>';
										echo '<input type="checkbox" name="wccd-items-check" value="1" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Mostra il metodo di pagamento solo se il/ i prodotti a carrello sono acquistabili con buoni <i>Carta del Docente</i>.<br>Più prodotti dovranno prevedere l\'uso di buoni dello stesso ambito di utilizzo.', 'wccd' ) ) . '</p>';

									echo wp_kses_post( $this->get_go_premium( true ) );
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccd-orders-on-hold">';
								echo '<th scope="row">' . esc_html__( 'Ordini in sospeso', 'wccd' ) . '</th>';
								echo '<td>';
										echo '<input type="checkbox" name="wccd-orders-on-hold" value="1" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'I buoni Carta del Docente verranno validati con il completamento manuale degli ordini.', 'wccd' ) ) . '</p>';

									echo wp_kses_post( $this->get_go_premium( true ) );
								echo '</td>';
							echo '<tr class="wccd-exclude-shipping">';
								echo '<th scope="row">' . esc_html__( 'Spese di spedizione', 'wccd' ) . '</th>';
								echo '<td>';
										echo '<input type="checkbox" name="wccd-exclude-shipping" value="1" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Escludi le spese di spedizione dal pagamento con Carta del Docente.', 'wccd' ) ) . '</p>';

									echo wp_kses_post( $this->get_go_premium( true ) );
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccd-coupon">';
								echo '<th scope="row">' . esc_html__( 'Conversione in coupon', 'wccd' ) . '</th>';
								echo '<td>';
									echo '<input type="checkbox" name="wccd-coupon" value="1" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Nel caso in cui il buono <i>Carta del Docente</i> inserito sia inferiore al totale a carrello, viene convertito in <i>Codice promozionale</i> ed applicato all\'ordine.', 'wccd' ) ) . '</p>';

									echo wp_kses_post( $this->get_go_premium( true ) );
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccd-email-order-received wccd-email-details">';
								echo '<th scope="row">' . esc_html__( 'Ordine ricevuto', 'wccd' ) . '</th>';
								echo '<td>';
									$default_order_received_message = __( 'L\'ordine verrà completato manualmente nei prossimi giorni e, contestualmente, verrà validato il buono Carta del Docente inserito. Riceverai una notifica email di conferma, grazie!', 'wccd' );
									echo '<textarea cols="6" rows="6" class="regular-text" name="wccd-email-order-received" placeholder="' . esc_html( $default_order_received_message ) . '" disabled></textarea>';
									echo '<p class="description">';
										echo wp_kses_post( __( 'Messaggio della mail inviata all\'utente al ricevimento dell\'ordine.', 'wccd' ) );
									echo '</p>';
									echo '<div class="wccd-divider"></div>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccd-email-subject wccd-email-details">';
								echo '<th scope="row">' . esc_html__( 'Oggetto email', 'wccd' ) . '</th>';
								echo '<td>';
										echo '<input type="text" class="regular-text" name="wccd-email-subject" placeholder="' . esc_attr__( 'Ordine fallito', 'wccd' ) . '" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Oggetto della mail inviata all\'utente nel caso in cui la validazione del buono non sia andata a buon fine.', 'wccd' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccd-email-heading wccd-email-details">';
								echo '<th scope="row">' . esc_html__( 'Intestazione email', 'wccd' ) . '</th>';
								echo '<td>';
										echo '<input type="text" class="regular-text" name="wccd-email-heading" placeholder="' . esc_attr__( 'Ordine fallito', 'wccd' ) . '" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Intestazione della mail inviata all\'utente nel caso in cui la validazione del buono non sia andata a buon fine.', 'wccd' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccd-email-order-failed wccd-email-details">';
								echo '<th scope="row">' . esc_html__( 'Ordine fallito', 'wccd' ) . '</th>';
								echo '<td>';
										$default_order_failed_message = __( 'La validazone del buono Carta del Docente ha restituito un errore e non è stato possibile completare l\'ordine, effettua il pagamento a <a href="[checkout-url]">questo indirizzo</a>.' );
										echo '<textarea cols="6" rows="6" class="regular-text" name="wccd-email-order-failed" placeholder="' . esc_html( $default_order_failed_message ) . '" disabled></textarea>';
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
				echo '<iframe width="300" height="1300" scrolling="no" src="https://www.ilghera.com/images/wccd-iframe.html"></iframe>';
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

			/*Immagine in pagina di checkout*/
			$wccd_image = isset( $_POST['wccd-image'] ) ? sanitize_text_field( wp_unslash( $_POST['wccd-image'] ) ) : '';
			update_option( 'wccd-image', $wccd_image );

		}
	}

}
new WCCD_Admin();

