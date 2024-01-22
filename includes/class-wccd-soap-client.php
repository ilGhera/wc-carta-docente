<?php
/**
 * Gestice le chiamate del web service
 *
 * @author ilGhera
 * @package wc-carta-docente/includes
 *
 * @since 1.4.0
 */

/**
 * WCCD_Soap_Client class
 */
class WCCD_Soap_Client {

	/**
	 * Opzione sandbox
	 *
	 * @var bool
	 */
	public $sandbox;

	/**
	 * Il certificato .pem
	 *
	 * @var string
	 */
	public $local_cert;

	/**
	 * L'endpoint
	 *
	 * @var string
	 */
	public $location;

	/**
	 * La password legata al certificato
	 *
	 * @var string
	 */
	public $passphrase;

	/**
	 * Il file WSDL previsto da Carta del Docente
	 *
	 * @var string
	 */
	public $wsdl;

	/**
	 * Il buono Carta del Docente
	 *
	 * @var string
	 */
	public $codice_voucher;

	/**
	 * Il valore del buono
	 *
	 * @var float
	 */
	public $import;


	/**
	 * The constructor
	 *
	 * @param string $codice_voucher il codice Carta del Docente.
	 * @param float  $import         il valore del buono.
	 *
	 * @return void
	 */
	public function __construct( $codice_voucher, $import ) {

		$this->sandbox = get_option( 'wccd-sandbox' );

		if ( $this->sandbox ) {
			$this->local_cert = WCCD_DIR . 'demo/wccd-demo-certificate.pem';
			$this->location   = 'https://wstest.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher';
			$this->passphrase = 'm3D0T4aM';

		} else {
			$this->local_cert = WCCD_PRIVATE . $this->get_local_cert();
			$this->location   = 'https://ws.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher';
			$this->passphrase = $this->get_user_passphrase();
		}

		$this->wsdl           = WCCD_INCLUDES_URI . 'VerificaVoucher.wsdl';
		$this->codice_voucher = $codice_voucher;
		$this->import         = $import;

	}


	/**
	 * Restituisce il nome del certificato presente nella cartella "Private"
	 *
	 * @return string
	 */
	public function get_local_cert() {
		$cert = wccd_admin::get_the_file( '.pem' );
		if ( $cert ) {
			return esc_html( basename( $cert ) );
		}
	}


	/**
	 * Restituisce la password memorizzata dall'utente nella compilazione del form
	 *
	 * @return string
	 */
	public function get_user_passphrase() {
		return base64_decode( get_option( 'wccd-password' ) );
	}


	/**
	 * Istanzia il SoapClient
	 */
	public function soap_client() {
		$soap_client = new SoapClient(
			$this->wsdl,
			array(
				'local_cert'     => $this->local_cert,
				'location'       => $this->location,
				'passphrase'     => $this->passphrase,
				'stream_context' => stream_context_create(
					array(
						'http' => array(
							'user_agent' => 'PHP/SOAP',
						),
						'ssl'  => array(
							'verify_peer'      => false,
							'verify_peer_name' => false,
						),
					)
				),
			)
		);

		return $soap_client;
	}


	/**
	 * Chiamata Check di tipo 1 e 2
	 *
	 * @param  integer $value il tipo di operazione da eseguire
	 * 1 per solo controllo
	 * 2 per scalare direttamente il valore del buono.
	 */
	public function check( $value = 1 ) {
		$check = $this->soap_client()->Check(
			array(
				'checkReq' => array(
					'tipoOperazione' => $value,
					'codiceVoucher'  => $this->codice_voucher,
				),
			)
		);

		return $check;
	}


	/**
	 * Chiamata Confirm utile ad utilizzare solo parte del valore del buono
	 */
	public function confirm() {
		$confirm = $this->soap_client()->Confirm(
			array(
				'checkReq' => array(
					'tipoOperazione' => '1',
					'codiceVoucher'  => $this->codice_voucher,
					'importo'        => $this->import,
				),
			)
		);

		return $confirm;
	}

}
