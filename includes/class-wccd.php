<?php
/**
 * Class WCCD
 *
 * @author ilGhera
 * @package wc-carta-docente/includes
 * @since 0.9.2
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

        /* Temp */
        /* add_action( 'woocommerce_order_status_changed', array( $this, 'complete_process_code' ), 10, 4 ); */
        add_action( 'woocommerce_order_status_completed', array( $this, 'complete_process_code' ), 10, 1 );

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
        $sandbox   = get_option( 'wccd-sandbox' );

        if ( $available ) {

            if ( $sandbox || ( wccd_admin::get_the_file( '.pem' ) && get_option( 'wccd-cert-activation' ) ) ) {

                $methods[] = 'WCCD_Teacher_Gateway'; 

            }

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


    function refused_code_customer_notification( $order_id, $order ) {
      
        $heading = $subject = 'Order Refused';
      
        // Get WooCommerce email objects
        $mailer = WC()->mailer()->get_emails();
        error_log( 'MAILER: ' . print_r( $mailer, true ) );
      
        // Use one of the active emails e.g. "Customer_Completed_Order"
        // Wont work if you choose an object that is not active
        // Assign heading & subject to chosen object
        $mailer['WC_Email_Failed_Order']->settings['heading']    = $heading;
        $mailer['WC_Email_Failed_Order']->settings['subject']    = $subject;
        $mailer['WC_Email_Failed_Order']->settings['recipient'] = $order->get_billing_email();
        /* $mailer['WC_Email_Customer_On_Hold_Order']->settings['subject'] = $subject; */

      
        // Send the email with custom heading & subject
        /* $mailer['WC_Email_Customer_On_Hold_Order']->trigger( $order_id ); */
        $mailer['WC_Email_Failed_Order']->trigger( $order_id );
      
        // To add email content use https://businessbloomer.com/woocommerce-add-extra-content-order-email/
        // You have to use the email ID chosen above and also that $order->get_status() == "refused"
      
    }


    /* public function complete_process_code( $order_id, $old_status, $new_status, $order  ) { */
    public function complete_process_code( $order_id ) {

        error_log( 'ORDER ID: ' . $order_id );
        /* error_log( 'OLS STATUS: ' . $old_status ); */
        /* error_log( 'NEW STATUS: ' . $new_status ); */
        $order = wc_get_order( $order_id );

        if ( is_object( $order ) && ! is_wp_error( $order ) ) {

            /* if ( 'docente' === $order->get_payment_method() && 'completed' === $new_status ) { */
            if ( 'docente' === $order->get_payment_method() ) {

                $teacher_code = get_post_meta( $order_id, 'wc-codice-docente', true );
                $total        = $order->get_total();
                $validate     = 2; //WCCD_Teacher_Gateway::process_code( $order_id, $teacher_code, $total, false, true );

                if ( 1 !== intval( $validate ) ) {

                    /* error_log( 'ERROR: ' . print_r( $validate, true ) ); */
                    $order->update_status( 'wc-failed' );
                    
                    $this->refused_code_customer_notification( $order_id, $order );

                    add_filter(
                        'woocommerce_email_enabled_customer_completed_order',
                        function() {
                            return false;
                        }
                    );

                } else {

                    error_log( 'VALIDATE: ' . print_r( $validate, true ) );

                }

            }

        }

    }


    public function process_code( $order_id ) {

        $order = wc_get_order( $order_id );
        error_log( 'ORDER: ' . print_r( $order, true ) );

    }

}

new WCCD();

