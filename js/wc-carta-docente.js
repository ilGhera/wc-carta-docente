/**
 * WC Carta Docente - js
 *
 * @author ilGhera
 * @package wc-carta-docente/js
 * @version 1.0.5
 */
var wccdController = function() {

	var self = this;

	self.onLoad = function() {

        self.checkForCoupon();

    }

    self.checkForCoupon = function() {
    
        jQuery(document).ready(function($){
            
            $('body').on('checkout_error', function() {
                
                console.log(wccdOptions.ajaxURL);

                var data = {
                    'action': 'check-for-coupon'
                }

                $.post(wccdOptions.ajaxURL, data, function(response) {
                    
                    if (response) {

                        $('body').trigger('update_checkout');
                    
                    }

                })

            })

        })
            
    }

}

/**
 * Class starter with onLoad method
 */
jQuery(document).ready(function($) {
	
	var Controller = new wccdController;
	Controller.onLoad();

});

