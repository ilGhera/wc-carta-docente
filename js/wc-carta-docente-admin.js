/**
 * WC Carta Docente - Admin js
 * @author ilGhera
 * @package wc-carta-docente/js
 *
 * @since 1.4.0
 */

/**
 * Ajax - Elimina il certificato caricato precedentemente
 */
var wccd_delete_certificate = function() {
	jQuery(function($){
		$('.wccd-delete-certificate').on('click', function(){
			var sure = confirm('Sei sicuro di voler eliminare il certificato?');
			if(sure) {
				var cert = $('.cert-loaded').text();
				var data = {
					'action': 'wccd-delete-certificate',
					'wccd-delete': true,
                    'delete-nonce': wccdData.delCertNonce,
					'cert': cert
				}			
				$.post(ajaxurl, data, function(response){
					location.reload();
				})
			}
		})	
	})
}
wccd_delete_certificate();


/**
 * Aggiunge un nuovo abbinamento bene/ categoria per il controllo in pagina di checkout
 */
var wccd_add_cat = function() {
	jQuery(function($){
		$('.add-cat-hover.wccd').on('click', function(){
			var number = $('.setup-cat').length + 1;

			/*Beni già impostati da escludere*/
			var beni_values = [];
			$('.wccd-field.beni').each(function(){
				beni_values.push($(this).val());
			})

			var data = {
				'action': 'wccd-add-cat',
				'number': number,
				'exclude-beni': beni_values.toString(),
                'add-cat-nonce': wccdData.addCatNonce,
			}
			$.post(ajaxurl, data, function(response){
				$(response).appendTo('.categories-container');
				$('.wccd-tot-cats').val(number);
			})				
		})
	})
}
wccd_add_cat();


/**
 * Rimuove un abbinamento bene/ categoria
 */
var wccd_remove_cat = function() {
	jQuery(function($){
		$(document).on('click', '.remove-cat-hover', function(response){
			var cat = $(this).closest('li');
			$(cat).remove();
			var number = $('.setup-cat').length;
			$('.wccd-tot-cats').val(number);
		})
	})
}
wccd_remove_cat();


/**
 * Funzionalità Sandbox
 */
var wccd_sandbox = function() {
	jQuery(function($){

        var data, sandbox;
        var nonce = $('#wccd-sandbox-nonce').attr('value');
        
        $(document).ready(function() {

            if ( 'wccd-certificate' == $('.nav-tab.nav-tab-active').data('link') ) {

                if ( $('.wccd-sandbox-field .tzCheckBox').hasClass( 'checked' ) ) {
                    $('#wccd-certificate').hide();
                    $('#wccd-sandbox-option').show();

                } else {
                    $('#wccd-certificate').show();
                    $('#wccd-sandbox-option').show();
                }

            }

        })

        $(document).on( 'click', '.wccd-sandbox-field .tzCheckBox', function() {

            if ( $(this).hasClass( 'checked' ) ) {
                $('#wccd-certificate').hide();
                sandbox = 1;
            } else {
                $('#wccd-certificate').show('slow');
                sandbox = 0;
            }

            data = {
                'action': 'wccd-sandbox',
                'sandbox': sandbox,
                'nonce': nonce
            }

            $.post(ajaxurl, data);

        })

    })
}
wccd_sandbox();


/**
 * Menu di navigazione della pagina opzioni
 */
var wccd_menu_navigation = function() {
	jQuery(function($){
		var contents = $('.wccd-admin');
		var url = window.location.href.split("#")[0];
		var hash = window.location.href.split("#")[1];

		if(hash) {
	        contents.hide();		    
            
            if( 'wccd-certificate' == hash ) {
                wccd_sandbox();
            } else {
                $('#' + hash).fadeIn(200);		
            }

	        $('h2#wccd-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
	        $('h2#wccd-admin-menu a').each(function(){
	        	if($(this).data('link') == hash) {
	        		$(this).addClass('nav-tab-active');
	        	}
	        })
	        
	        $('html, body').animate({
	        	scrollTop: 0
	        }, 'slow');
		}

		$("h2#wccd-admin-menu a").click(function () {
	        var $this = $(this);
	        
	        contents.hide();
	        $("#" + $this.data("link")).fadeIn(200);

            if( 'wccd-certificate' == $this.data("link") ) {
                $('#wccd-sandbox-option').fadeIn(200);
            
                wccd_sandbox();
            
            }
	        
            $('h2#wccd-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
	        $this.addClass('nav-tab-active');

	        window.location = url + '#' + $this.data('link');

	        $('html, body').scrollTop(0);

	    })

	})
}
wccd_menu_navigation();

/**
 * Mostra i dettagli della mail all'utente
 * nel caso la funzione ordini in sospeso sia stata attivata
 *
 * @return void
 */
var wccd_email_details = function() {
    jQuery(function($){
        $(document).ready(function() {

            var on_hold       = $('.wccd-orders-on-hold');
            var email_details = $('.wccd-email-details');

            if ( $('.tzCheckBox', on_hold).hasClass( 'checked' ) ) {
                $(email_details).show();
            }

            $('.tzCheckBox', on_hold).on( 'click', function() {

                if ( $(this).hasClass( 'checked' ) ) {
                    $(email_details).show('slow');
                } else {
                    $(email_details).hide();
                }

            })
            
        })
    })
}
wccd_email_details();

/**
 * Attivazione opzione coupon con esclusione spese di spedizione
 *
 * @return void
 */
var wccd_exclude_shipping = function() {

    jQuery(function($){
        $(document).ready(function() {

            var excludeShipping = $('.wccd-exclude-shipping');
            var coupon          = $('.wccd-coupon');

            $('.tzCheckBox', excludeShipping).on( 'click', function() {

                if ( $(this).hasClass( 'checked' ) && ! $('.tzCheckBox', coupon).hasClass( 'checked' ) ) {
                    $('.tzCheckBox', coupon).trigger('click');
                }

            })

            // Non disattivare opzione coupon con esclusione spese di spedizione attive
            $('.tzCheckBox', coupon).on( 'click', function() {

                if ( ! $(this).hasClass( 'checked' ) && $('.tzCheckBox', excludeShipping).hasClass( 'checked' ) ) {
                    alert( 'L\'esclusione delle spese di spedizione prevedere l\'utilizzo di questa funzionalità.' );
                    $('.tzCheckBox', coupon).trigger('click');
                }

            })
        })
    })

}
wccd_exclude_shipping();
