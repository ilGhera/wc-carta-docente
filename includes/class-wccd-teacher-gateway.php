<?php
/**
 * Estende la classe WC_Payment_Gateway di WooCommerce aggiungendo il nuovo gateway "buono docente".
 *
 * @author ilGhera
 * @package wc-carta-docente/includes
 * @version 0.9.2
 */
class WCCD_Teacher_Gateway extends WC_Payment_Gateway {

    
    public static $coupon_option;


	public function __construct() {

		$this->plugin_id          = 'woocommerce_carta_docente';
		$this->id                 = 'docente';
		$this->has_fields         = true;
		$this->method_title       = 'Buono docente';
		$this->method_description = 'Consente ai docenti di utilizzare il buono a loro riservato per l\'acquisto di materiale didattico.';
		
        self::$coupon_option      = get_option( 'wccd-coupon' );

        if ( get_option( 'wccd-image' ) ) {

            $this->icon = WCCD_URI . 'images/carta-docente.png';			

		}

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option('title');
		$this->description = $this->get_option('description');
        
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'unset_teacher_gateway' ) );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_teacher_code' ), 10, 1 );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'display_teacher_code'), 10, 1 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_teacher_code' ), 10, 1 );
	}


    /**
     * Disabilita il metodo di pagamento se i prodotti a carrello richiedono buoni con ambito differente
     *
     * @param array $available_gateways I metodi di pagamento disponibili.
     *
     * @return array I metodi aggiornati
     */
    public function unset_teacher_gateway( $available_gateways ) {

        if ( is_admin() || ! is_checkout() || ! get_option('wccd-items-check') ) {

            return $available_gateways;

        }

        $unset      = false;
        $cat_ids    = array();
        $categories = get_option('wccd-categories');
        error_log( 'CATEGORIES 1: ' . print_r( $categories, true ) );

        if ( empty( $categories ) ) {

            return $available_gateways;

        }

        /* $categories = call_user_func_array( 'array_merge', $categories ); */

        if ( is_array( $categories ) ) {
            
            foreach ( $categories as $key => $value ) {

                if ( is_array( $value ) ) {
                    
                    /* error_log( 'ARRAY VALUES: ' . print_r( array_values( $value ), true ) ); */
                    $cat_ids = array_unique( array_merge( $cat_ids, array_values( $value ) ) );

                }

            }

        }

        /* foreach ( $categories as $cat ) { */

        /*     $cat_ids[] = $cat; */

        /* } */
        error_log( 'CAT IDS: ' . print_r( $cat_ids, true ) );

        $items_term_ids = array();
        
        foreach ( WC()->cart->get_cart_contents() as $key => $values ) {

            $item_ids = array();
            $terms    = get_the_terms( $values['product_id'], 'product_cat' );    

            foreach ( $terms as $term ) {        

                $item_ids[] = $term->term_id;

            }

            error_log( 'ITEM IDS: ' . print_r( $item_ids, true ) );
            $results = array_intersect( $item_ids, $cat_ids );

            if ( ! is_array( $results ) || empty( $results ) ) {

                $unset = true;

            } else {

                $items_term_ids[] = $results;
            }

        }

        error_log( 'ITEMS TERM IDS: ' . print_r( $items_term_ids, true ) );

        if ( ! $unset && ! empty( $items_term_ids ) ) {

            $intersect = call_user_func_array( 'array_intersect', $items_term_ids );

            if ( empty( $intersect ) ) {

                $unset = true;

            }
        }

        if ( $unset ) {

            unset( $available_gateways['docente'] );
        
        }

        return $available_gateways;

    }


	/**
	 * Campi relativi al sistema di pagamento, modificabili nel back-end
	 */
	public function init_form_fields() {
		
		$this->form_fields = apply_filters( 'wc_offline_form_fields',array(
			'enabled' => array(
		        'title'   => __( 'Enable/Disable', 'woocommerce' ),
		        'type'    => 'checkbox',
		        'label'   => __( 'Abilita pagamento con buono docente', 'wccd' ),
		        'default' => 'yes',
		    ),
		    'title' => array(
		        'title'       => __( 'Title', 'woocommerce' ),
		        'type'        => 'text',
		        'description' => __( 'This controls the title which the user sees during checkout.', 'wccd' ),
		        'default'     => __( 'Buono docente', 'wccd' ),
		        'desc_tip'    => true,
		    ),
		    'description' => array(
		        'title'   => __( 'Messaggio utente', 'woocommerce' ),
		        'type'    => 'textarea',
		        'default' => 'Consente ai docenti di utilizzare il buono a loro riservato per l\'acquisto di materiale didattico.',
		    )
		));

	}


	/**
	 * Campo per l'inserimento del buono nella pagina di checkout 
	 */
	public function payment_fields() {
		?>
		<p>
			<?php echo $this->description; ?>
			<label for="wc-codice-docente">
				<?php echo __('Inserisci qui il tuo codice', 'wccd');?>
				<span class="required">*</span>
			</label>
			<input type="text" class="wc-codice-docente" id="wc-codice-docente" name="wc-codice-docente" />
		</p>
		<?php
	}


	/**
	 * Restituisce la cateogia prodotto corrispondente al bene acquistabile con il buono
     *
	 * @param  string $purchasable bene acquistabile
     *
	 * @return int l'id di categoria acquistabile
	 */
	public static function get_purchasable_cats( $purchasable ) {

		$wccd_categories = get_option( 'wccd-categories' );

		if ( $wccd_categories ) {
	
			$purchasable = str_replace( '(', '', $purchasable );
			$purchasable = str_replace( ')', '', $purchasable );
			$bene        = strtolower( str_replace( ' ', '-', $purchasable ) );
			
			$output = array();

			for ( $i=0; $i < count( $wccd_categories ); $i++ ) { 

				if ( array_key_exists( $bene, $wccd_categories[ $i ] ) ) {

					$output[] = $wccd_categories[ $i ][ $bene ];

				}

			}

			return $output;
				
		}

	}


	/**
	 * Tutti i prodotti dell'ordine devono essere della tipologia (cat) consentita dal buono docente. 
	 * @param  object $order  
	 * @param  string $bene il bene acquistabile con il buono
	 * @return bool
	 */
	public static function is_purchasable( $order, $bene ) {

		$cats   = self::get_purchasable_cats( $bene );
		$items  = $order->get_items();
		$output = true;
		
		if ( is_array( $cats ) && ! empty( $cats ) ) {

			foreach ( $items as $item ) {
				$terms = get_the_terms( $item['product_id'], 'product_cat' );
				$ids   = array();

				foreach ( $terms as $term ) {

					$ids[] = $term->term_id;

				}

				$results = array_intersect( $ids, $cats );

				if ( ! is_array( $results ) || empty( $results ) ) {

					$output = false;
					continue;

				}

			}		

		}

		return $output;
		
	}


	/**
	 * Mostra il buono docente nella thankyou page, nelle mail e nella pagina dell'ordine.
	 * @param  object $order
	 * @return mixed        testo formattato con il buono utilizzato per l'acquisto
	 */
	public function display_teacher_code( $order ) {
		
		$data = $order->get_data();

		if ( $data['payment_method'] === 'docente' ) {

		    echo '<p><strong>' . __( 'Buono docente', 'wccd' ) . ': </strong>' . get_post_meta( $order->get_id(), 'wc-codice-docente', true ) . '</p>';

		}
	}


    /**
     * Ricava il coupon id dal suo codice
     *
     * @param string $coupon_code il codice del coupon.
     *
     * @return int l'id del coupon
     */
    private static function get_coupon_id( $coupon_code ) {

        $coupon = get_page_by_title( $coupon_code, OBJECT, 'shop_coupon' );
        
        if ( $coupon && isset( $coupon->ID ) ) {

            return $coupon->ID;

        }

    }

    /**
     * Crea un nuovo coupon
     *
     * @param int    $order_id     l'id dell'ordine.
     * @param float  $amount       il valore da assegnare al coupon.
     * @param string $teacher_code il codice del buono docente.
     *
     * @return int l'id del coupon creato
     */
    private static function create_coupon( $order_id, $amount, $teacher_code ) {
        
        $coupon_code = 'wccd-' . $order_id . '-' . $teacher_code;

        $args = array(
            'post_title'   => $coupon_code,
            'post_content' => '',
            'post_excerpt' => $teacher_code,
            'post_type'    => 'shop_coupon',
            'post_status'  => 'publish',
            'post_author'  => 1,
            'meta_input'   => array(
                'discount_type' => 'fixed_cart',
                'coupon_amount' => $amount,
                'usage_limit'   => 1,
            ),
        );

        $coupon_id = self::get_coupon_id( $coupon_code );

        /* Aggiorna coupon se già presente */
        if ( $coupon_id ) {

            $args['ID'] = $coupon_id;
            $coupon_id  = wp_update_post( $args );
            
        } else {

            $coupon_id = wp_insert_post( $args );

        }

        if ( ! is_wp_error( $coupon_id ) ) {

            return $coupon_code;

        }

    }


    /**
     * Processa il buono docente inserito
     *
     * @param int    l'id dell'ordine.
     * @param string il buono docente.
     * @param float  il totale dell'ordine o il valore del coupon.
     * @param bool   se valorizzato il metodo viene utilizzato nella validazione del coupon - process_coupon().
     *
     * @return mixed string in caso di errore, 1 in alternativa
     */
    public static function process_code( $order_id, $teacher_code, $import, $converted = false ) {

        global $woocommerce;

        $output     = 1; 
        $order      = wc_get_order( $order_id );
        $soapClient = new wccd_soap_client( $teacher_code, $import );
        
        try {

            /*Prima verifica del buono*/
            $response      = $soapClient->check();
            $bene          = $response->checkResp->ambito; //il bene acquistabile con il buono inserito
            $importo_buono = floatval($response->checkResp->importo); //l'importo del buono inserito
            
            /*Verifica se i prodotti dell'ordine sono compatibili con i beni acquistabili con il buono*/
            $purchasable = self::is_purchasable( $order, $bene );

            if ( ! $purchasable ) {

                $output = __( 'Uno o più prodotti nel carrello non sono acquistabili con il buono inserito.', 'wccd' );

            } else {

                $type = null;

                if ( self::$coupon_option && $importo_buono < $import && ! $converted  ) {

                    /* Creazione coupon */
                    $coupon_code = self::create_coupon( $order_id, $importo_buono, $teacher_code );

                    if ( $coupon_code && ! WC()->cart->has_discount( $coupon_code ) ) {

                        /* Coupon aggiunto all'ordine */
                        WC()->cart->apply_coupon( $coupon_code );

                        $output = __( 'Il valore del buono inserito non è sufficiente ed è stato convertito in buono sconto.', 'wccd' );

                    }

                } elseif ( $importo_buono === $import ) {

                    $type = 'check';

                } else {

                    $type = 'confirm';

                }

                if ( $type ) {

                    try {

                        /*Operazione differente in base al rapporto tra valore del buono e totale dell'ordine*/
                        $operation = $type === 'check' ? $soapClient->check( 2 ) : $soapClient->confirm();

                        /*Aggiungo il buono docente all'ordine*/
                        update_post_meta( $order_id, 'wc-codice-docente', $teacher_code );

                        if ( ! $converted ) {

                            /*Ordine completato*/
                            $order->payment_complete();

                            /*Svuota carrello*/ 
                            $woocommerce->cart->empty_cart();	

                        }

                    } catch ( Exception $e ) {
        
                        $output = $e->detail->FaultVoucher->exceptionMessage;
                   
                    } 

                }

            }

        } catch ( Exception $e ) {

            $output = $e->detail->FaultVoucher->exceptionMessage;
        
        }

        return $output;

    }


	/**
	 * Gestisce il processo di pagamento, verificando la validità del buono inserito dall'utente
	 * @param  int $order_id l'id dell'ordine
	 */
	public function process_payment( $order_id ) {

	    $order  = wc_get_order( $order_id );
		$import = floatval( $order->get_total() ); //il totale dell'ordine

		$notice = null;
		$output = array(
			'result'   => 'failure',
			'redirect' => '',
		);

		$data         = $this->get_post_data();
	    $teacher_code = $data['wc-codice-docente']; //il buono inserito dall'utente

        if ( $teacher_code ) {

            $notice = self::process_code( $order_id, $teacher_code, $import );

            if ( 1 === intval( $notice ) ) {

                $output = array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order ),
                );

            } else {

                wc_add_notice( __( 'Buono docente - ' . $notice, 'wccd' ), 'error' );

            }

	    }	
		
		return $output;

	}

}

