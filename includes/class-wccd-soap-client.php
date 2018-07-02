<?php

class wccd_soap_client {

    public function __construct($codiceVoucher, $import) {
		$this->wsdl = WCCD_PRIVATE . 'VerificaVoucher.wsdl';
        $this->local_cert = $this->get_local_cert();
        $this->location = 'https://ws.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher';
        $this->codiceVoucher = $codiceVoucher;
		$this->import = $import;
	}


    /**
     * Restituisce il nome del certificato presente nella cartella "Private"
     * @return string
     */
    public function get_local_cert() {
        $cert = wccd_admin::get_the_file('.pem');
        if($cert) {
            return esc_html(basename($cert));
        }
    }


	/**
     * Istanzia il SoapClient
     */
    public function soap_client() {
        $soapClient = new SoapClient(
            $this->wsdl, 
            array(
              'local_cert'  => $this->local_cert,
              'location'    => $this->location,
              'passphrase'  => 'fullgas'
            )
        );
  
        return $soapClient;
	}


    /**
     * Chiamata Check di tipo 1 e 2
     * @param  integer $value il tipo di operazione da eseguire
     * 1 per solo controllo
     * 2 per scalare direttamente il valore del buono
     */
	public function check($value = 1) {
        $check = $this->soap_client()->Check(array(
        	'checkReq' => array(
        		'tipoOperazione' => $value,
        		'codiceVoucher'  => $this->codiceVoucher
        	)
        ));

        return $check;
	}


    /**
     * Chiamata Confirm utile ad utilizzare solo parte del valore del buono
     */
	public function confirm() {
        $confirm = $this->soap_client()->Confirm(array(
        	'checkReq' => array(
        		'tipoOperazione' => '1',
        		'codiceVoucher'  => $this->codiceVoucher,
        		'importo'=> $this->import
        	)
        ));

        return $confirm;
	}

}