<?php
/**
 * Estende la classe WC_Payment_Gateway di WooCommerce 
 * aggiungendo il nuovo gateway "buono docente".
 */
class WC_Teacher_Gateway extends WC_Payment_Gateway {


	public function __construct() {
		$this->plugin_id = 'woocommerce_carta_docente';
		$this->id = 'docente';
		$this->has_fields = true;
		$this->method_title = 'Buono docente';
		$this->method_description = 'Consente ai docenti di utilizzare il buono a loro riservato per l\'acquisto di materiale didattico.';
		$this->icon = WCCD_URI . 'images/carta-docente.png';

		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');


		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}


	/**
	 * Campi relativi al sistema di pagamento, modificabili nel back-end
	 */
	public function init_form_fields() {
		
		$this->form_fields = apply_filters( 'wc_offline_form_fields',array(
			'enabled' => array(
		        'title' => __( 'Enable/Disable', 'woocommerce' ),
		        'type' => 'checkbox',
		        'label' => __( 'Abilita', 'woocommerce' ),
		        'default' => 'yes'
		    ),
		    'title' => array(
		        'title' => __( 'Title', 'woocommerce' ),
		        'type' => 'text',
		        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		        'default' => __( 'Buono docente', 'woocommerce' ),
		        'desc_tip'      => true,
		    ),
		    'description' => array(
		        'title' => __( 'Messaggio utente', 'woocommerce' ),
		        'type' => 'textarea',
		        'default' => 'Consente ai docenti di utilizzare il buono a loro riservato per l\'acquisto di materiale didattico.',
		        // 'desc_tip'      => true,
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
				<?php echo __('Inserisci qui il tuo codice', 'woocommerce');?>
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
		$cat = null;
		switch (strtolower($purchasable)) {
			case 'libri':
				$cat = 57;
				break;
			case 'testi':
				$cat = 57;
				break;			
			case 'software':
				$cat = 56;
				break;			
			case 'hardware':
				$cat = 56;
				break;
			case '-':
				$cat = 56; //temp
				break;			
		}

		return $cat;
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
	 * Gestisce il processo di pagamento, verificando la validità del buono inserito dall'utente
	 * @param  int $order_id l'id dell'ordine
	 */
	public function process_payment($order_id) {

		global $woocommerce;
	    $order = new WC_Order($order_id);
		$import = $order->get_total(); //il totale dell'ordine

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

				$bene    = $response->checkResp->bene; //il bnee acquistabile con il buono inserito
			    $importo = $response->checkResp->importo; //l'importo del buono inserito
			    
			    /*Verifica se i prodotti dell'ordine sono compatibili con i beni acquistabili con il buono*/
			    $purchasable = $this->is_purchasable($order, $bene);

			    if(!$purchasable) {

					$notice = __('Uno o più prodotti nel carrello non sono acquistabili con il buono inserito.', 'woothemes');

				} else {

					$type = null;
					if($response->checkResp->importo <= $import) {

						$type = 'check';

					} elseif($response->checkResp->importo > $import) {

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
			wc_add_notice( __('<b>Buono docente</b> - ' . $notice, 'woothemes'), 'error' );
		}

		return $output;

	}

}


/**
 * Aggiunge il nuovo gateway a quelli disponibili in WooCommerce
 * @param array $methods gateways disponibili 
 */
function add_teacher_gateway_class($methods) {
    $methods[] = 'WC_Teacher_Gateway'; 
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_teacher_gateway_class');	
