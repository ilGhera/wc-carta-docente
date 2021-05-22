<?php
/**
 * Class WCCD
 *
 * @author ilGhera
 * @package wc-carta-docente/includes
 * @since 1.1.1
 */
class WCCD {

    
    public $coupon_option;
    

    /**
     * The constructor
     *
     * @return void
     */
    public function __construct() {

        /* Controlla se l'optione è stata attivata dall'admin */
        $this->coupon_option = get_option( 'wccd-coupon' );

        /* Actions */
        add_action( 'wp_ajax_check-for-coupon', array( $this, 'wccd_check_for_coupon' ) );
        add_action( 'wp_ajax_nopriv_check-for-coupon', array( $this, 'wccd_check_for_coupon' ) );
        add_action( 'woocommerce_checkout_process', array( $this, 'process_coupon' ) );

        /* Filters */
        add_filter( 'woocommerce_payment_gateways', array( $this, 'wccd_add_teacher_gateway_class' ) );

    }


    /**
     * Restituisce i dati della sessione WC corrente
     *
     * @return array
     */
    public function get_session_data() {

        $session = WC()->session;

        if ( $session ) {

            return $session->get_session_data();

        }

    }


    /**
     * Verifica se in sessione è stato applicato un coupon derivante da un buono Carta del Docente
     *
     * @param bool $return restituisce il codice del coupon se valorizzato.
     *
     * @return mixed
     */
    public function wccd_coupon_applied( $return = false ) {

        $output       = false;
        $session_data = $this->get_session_data();

        if ( $session_data ) {

            $coupons = isset( $session_data['applied_coupons'] ) ? maybe_unserialize( $session_data['applied_coupons'] ) : null;
            
            if ( $coupons && is_array( $coupons ) ) {

                foreach ( $coupons as $coupon ) {

                    if ( false !== strpos( $coupon, 'wccd' ) ) {
                        
                        if ( $return ) {

                            $output = $coupon; 

                        } else {

                            $output = true;

                        }

                        continue;

                    }
                }

            }

        }

        return $output;

    }


    /**
     * Verifica di un coupon wccd in sessione al click di aquisto in pagina di checkout
     *
     * @return void
     */
    public function wccd_check_for_coupon() {
        
        echo $this->wccd_coupon_applied();

        exit;

    }


    /**
     * Se presente un certificato, aggiunge il nuovo gateway a quelli disponibili in WooCommerce
     *
     * @param array $methods gateways disponibili 
     *
     * @return array
     */
    public function wccd_add_teacher_gateway_class( $methods ) {
        
        $available = ( $this->coupon_option && $this->wccd_coupon_applied() ) ? false : true;

        if ( $available && wccd_admin::get_the_file( '.pem' ) && get_option( 'wccd-cert-activation' ) ) {

            $methods[] = 'WCCD_Teacher_Gateway'; 

        }

        return $methods;

    }


    /**
     * Durante la creazione dell'ordine se presente un coupon wccd invia il buono a Carta del Docente
     * L'ordine viene bloccato se il buono non risulta essere valido
     *
     * @return void
     */
    public function process_coupon() {

        if ( $this->coupon_option ) {

            $coupon_code = $this->wccd_coupon_applied( true );

            if ( $coupon_code ) {

                $parts         = explode( '-', $coupon_code );
                $coupon        = new WC_Coupon( $coupon_code );
                $coupon_amount = $coupon->get_amount();
                $teacher_code  = $coupon->get_description();

                $notice = WCCD_Teacher_Gateway::process_code( $parts[1], $teacher_code, $coupon_amount, true );

                if ( 1 !== intval( $notice ) ) {

                    wc_add_notice( __( 'Buono docente - ' . $notice, 'wccd' ), 'error' );         

                } else {

                    /* Eliminazione ordine temporaneo */
                    wp_delete_post( $parts[1] );

                }
            }

        }
  
    }

}

new WCCD();

