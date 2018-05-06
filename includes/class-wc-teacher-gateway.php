<?php
/**
 * Extends the class WC_Payment_Gateway of WooCommerce 
 * adding adding the new gateway "buono docente".
 */
class WC_Teacher_Gateway extends WC_Payment_Gateway {

	public function __construct() {
		$this->plugin_id = 'woocommerce_carta_docente';
		$this->id = 'docente';
		$this->has_fields = true;
		$this->method_title = 'Buono docente';
		$this->method_description = 'Consente ai docenti di utilizzare il buono a loro riservato per l\'acquisto di materiale didattico.';
		$this->icon = WCCD_URI . 'images/18app-carta-docente.png';

		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');


		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

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

	public function get_response($type, $value = 1) {
		$data = $this->get_post_data();
	    $teacher_code = $data['wc-codice-docente'];

	    if($teacher_code) {
		    $soapClient = new wccd_soap_client($teacher_code, null);
		    $response = $soapClient->call($type, $value);	    

		    return $response;
	    }	
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
			//...			
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
			var_dump($terms);
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

	public function process_payment($order_id) {

		global $woocommerce;
	    $order = new WC_Order($order_id);
		$import = $order->get_total();

		$notice = null;
		$output = array(
			'result' => 'failure',
			'redirect' => ''
		);

		$response = $this->get_response('check');
		
		if(is_object($response)) {
			
			$bene    = $response->checkResp->bene;
		    $importo = $response->checkResp->importo;

		    $purchasable = $this->is_purchasable($order, $bene);

		    // var_dump($purchasable);


			/*The ticket is valid*/
			if($response->checkResp->nominativoBeneficiario) {

				if(!$purchasable) {

					$notice = __('Uno o più prodotti nel carrello non sono acquistabili con il buono inserito.', 'woothemes');

				} else {

					if($response->checkResp->importo < $import) {

						$notice = __('Sembra che il tuo buono non abbia un credito sufficiente per completare l\'acquisto.', 'woothemes');

					} else {

						if($response->checkResp->importo === $import) {
							
							$response = $this->get_response('check', 2);	//controllare esito

						} elseif($response->checkResp->importo > $import) {
							
							$confirm - $this->get_response('confirm');		//controllare esito					
						}

						if($confirm === 'OK') { // va bene solo per confirm
	
						    // Mark as compelte
						    $order->payment_complete();

						    // Reduce stock levels
						    // $order->reduce_order_stock();// Deprecated
						    // wc_reduce_stock_levels($order_id);

						    // Remove cart
						    $woocommerce->cart->empty_cart();	

						    $output = array(
						        'result' => 'success',
						        'redirect' => $this->get_return_url($order)
						    );

						} else {

							$notice = __('Il pagamento non è andato a buon fine, ti preghiamo di riprovare.', 'woothemes');

						}


					}


				}
			
			} else {

				$notice = $response;

			}	

		} else {

			$notice = __('Il codice inserito sembra non essere valido, la preghiamo di riprovare.', 'woothemes');
		
		}

		if($notice) {
			wc_add_notice( __('<b>Buono docente</b> - ' . $notice, 'woothemes'), 'error' );
		}

		return $output;

	}

}


/**
 * Add the new gateway
 * @param array $methods available gateways
 */
function add_teacher_gateway_class($methods) {
    $methods[] = 'WC_Teacher_Gateway'; 
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_teacher_gateway_class');	
