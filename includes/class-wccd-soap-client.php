<?php

class wccd_soap_client {

	public function __construct($codiceVoucher, $import) {
		$this->wsdl = WCCD_PRIVATE . 'VerificaVoucher.wsdl';
        $this->local_cert = WCCD_PRIVATE . 'defCert.pem';
        $this->location = 'https://ws.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher';
        $this->codiceVoucher = $codiceVoucher;
		$this->import = $import;
	}

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

	public function check($value = 1) {
        $check = $this->soap_client()->Check(array(
        	'checkReq' => array(
        		'tipoOperazione' => $value,
        		'codiceVoucher'  => $this->codiceVoucher
        	)
        ));

        return $check;
	}

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

	public function call($type, $value = 1) {
        try {
            $response = $type == 'check' ? $this->check($value) : $this->confirm();
        } catch(Exception $e) {
            $response = $e->detail->FaultVoucher->exceptionMessage;
        }     

        return $response;    
	}

}