<?php
/**
 * Gestice le chiamate del web service 
 * @author ilGhera
 * @package wc-carta-docente/includes
 * @since 1.2.0
 */
class wccd_soap_client {

    public function __construct($codiceVoucher, $import) {

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

        $this->wsdl          = WCCD_INCLUDES_URI . 'VerificaVoucher.wsdl';
        $this->codiceVoucher = $codiceVoucher;
        $this->import        = $import;

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
     * Restituisce la password memorizzata dall'utente nella compilazione del form
     * @return string
     */
    public function get_user_passphrase() {
        return base64_decode(get_option('wccd-password'));
    }


    /**
     * Istanzia il SoapClient
     */
    public function soap_client() {
        $soapClient = new SoapClient(
            $this->wsdl, 
            array(
                'local_cert'     => $this->local_cert,
                'location'       => $this->location,
                'passphrase'     => $this->passphrase,
                /* 'cache_wsdl' => WSDL_CACHE_BOTH, */
                'stream_context' => stream_context_create(
                    array(
                        'http' => array(
                            'user_agent' => 'PHP/SOAP',
                        ),
                        'ssl' => array(
                            'verify_peer'       => false,
                            'verify_peer_name'  => false,
                        ),
                    )
                ),
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
