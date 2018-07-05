<?php
/**
 * Estende la classe WC_Payment_Gateway di WooCommerce 
 * aggiungendo il nuovo gateway "buono docente".
 */
class WCCD_Teacher_Gateway extends WC_Payment_Gateway {

	public function __construct() {
		$this->plugin_id = 'woocommerce_carta_docente';
		$this->id = 'docente';
		$this->has_fields = true;
		$this->method_title = 'Buono docente';
		$this->method_description = 'Consente ai docenti di utilizzare il buono a loro riservato per l\'acquisto di materiale didattico.';
		
		if(get_option('wccd-image')) {
			$this->icon = WCCD_URI . 'images/carta-docente.png';			
		}

		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action('woocommerce_order_details_after_order_table', array($this, 'display_teacher_code'), 10, 1);
		add_action('woocommerce_email_after_order_table', array($this, 'display_teacher_code'), 10, 1);
		add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_teacher_code'), 10, 1);
	}


	/**
	 * Campi relativi al sistema di pagamento, modificabili nel back-end
	 */
	public function init_form_fields() {
		
		$this->form_fields = apply_filters( 'wc_offline_form_fields',array(
			'enabled' => array(
		        'title' => __( 'Enable/Disable', 'woocommerce' ),
		        'type' => 'checkbox',
		        'label' => __( 'Abilita pagamento con buono docente', 'wccd' ),
		        'default' => 'yes'
		    ),
		    'title' => array(
		        'title' => __( 'Title', 'woocommerce' ),
		        'type' => 'text',
		        'description' => __( 'This controls the title which the user sees during checkout.', 'wccd' ),
		        'default' => __( 'Buono docente', 'wccd' ),
		        'desc_tip'      => true,
		    ),
		    'description' => array(
		        'title' => __( 'Messaggio utente', 'woocommerce' ),
		        'type' => 'textarea',
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
	 * @param  string $purchasable bene acquistabile
	 * @return int                 l'id di categoria acquistabile
	 */
	public function get_purchasable_cat($purchasable) {

		$wccd_categories = get_option('wccd-categories');
		$bene = strtolower($purchasable);
		
		for($i=0; $i < count($wccd_categories); $i++) { 
			if(array_key_exists($bene, $wccd_categories[$i])) {
				return $wccd_categories[$i][$bene];
			}
		}

	}


	/**
	 * Tutti i prodotti dell'ordine devono essere della tipologia (cat) consentita dal buono docente. 
	 * @param  object $order  
	 * @param  string $bene il bene acquistabile con il buono
	 * @return bool
	 */
	public function is_purchasable($order, $bene) {
		$cat = $this->get_purchasable_cat($bene);

		$items = $order->get_items();

		$output = true;
		foreach ($items as $item) {
			$terms = get_the_terms($item['product_id'], 'product_cat');
			$ids = array();

			foreach($terms as $term) {
				$ids[] = $term->term_id;
			}

			if(!in_array($cat, $ids)) {
				$output = false;
				continue;
			}				

		}		
		
		return $output;
	}


	/**
	 * Mostra il buono docente nella thankyou page, nelle mail e nella pagina dell'ordine.
	 * @param  object $order
	 * @return mixed        testo formattato con il buono utilizzato per l'acquisto
	 */
	public function display_teacher_code($order) {
		
		$data = $order->get_data();

		if($data['payment_method'] === 'docente') {
		    echo '<p><strong>' . __('Buono docente', 'wccd') . ': </strong>' . get_post_meta($order->get_id(), 'wc-codice-docente', true) . '</p>';
		}
	}


	/**
	 * Gestisce il processo di pagamento, verificando la validità del buono inserito dall'utente
	 * @param  int $order_id l'id dell'ordine
	 */
	public function process_payment($order_id) {

		global $woocommerce;
	    $order = new WC_Order($order_id);
		$import = floatval($order->get_total()); //il totale dell'ordine

		$notice = null;
		$output = array(
			'result' => 'failure',
			'redirect' => ''
		);

		$data = $this->get_post_data();
	    $teacher_code = $data['wc-codice-docente']; //il buono inserito dall'utente

	    if($teacher_code) {

		    $soapClient = new wccd_soap_client($teacher_code, $import);
		    
		    try {

		    	/*Prima verifica del buono*/
	            $response = $soapClient->check();

				$bene    = $response->checkResp->bene; //il bene acquistabile con il buono inserito
			    $importo_buono = floatval($response->checkResp->importo); //l'importo del buono inserito
			    
			    /*Verifica se i prodotti dell'ordine sono compatibili con i beni acquistabili con il buono*/
			    $purchasable = $this->is_purchasable($order, $bene);

			    if(!$purchasable) {

					$notice = __('Uno o più prodotti nel carrello non sono acquistabili con il buono inserito.', 'wccd');

				} else {

					$type = null;
					if($importo_buono === $import) {

						$type = 'check';

					} else {

						$type = 'confirm';

					}

					if($type) {

						try {

							/*Operazione differente in base al rapporto tra valore del buono e totale dell'ordine*/
							$operation = $type === 'check' ? $soapClient->check(2) : $soapClient->confirm();

							/*Ordine completato*/
						    $order->payment_complete();

						    // Reduce stock levels
						    // $order->reduce_order_stock();// Deprecated
						    // wc_reduce_stock_levels($order_id);

						    /*Svuota carrello*/ 
						    $woocommerce->cart->empty_cart();	

						    /*Aggiungo il buono docente all'ordine*/
							update_post_meta($order_id, 'wc-codice-docente', $teacher_code);

						    $output = array(
						        'result' => 'success',
						        'redirect' => $this->get_return_url($order)
						    );

						} catch(Exception $e) {
			
				            $notice = $e->detail->FaultVoucher->exceptionMessage;
				       
				        } 

					}

				}

	        } catch(Exception $e) {

	            $notice = $e->detail->FaultVoucher->exceptionMessage;
	        
	        }  

	    }	
		
		if($notice) {
			wc_add_notice( __('<b>Buono docente</b> - ' . $notice, 'wccd'), 'error' );
		}

		return $output;

	}

}


/**
 * Se presente un certificato, aggiunge il nuovo gateway a quelli disponibili in WooCommerce
 * @param array $methods gateways disponibili 
 */
function wccd_add_teacher_gateway_class($methods) {
	if(wccd_admin::get_the_file('.pem')) {
	    $methods[] = 'WCCD_Teacher_Gateway'; 
	}

    return $methods;
}
add_filter('woocommerce_payment_gateways', 'wccd_add_teacher_gateway_class');