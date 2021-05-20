/**
 * WC Carta Docente - Admin js
 * @author ilGhera
 * @package wc-carta-docente/js
 * @version 1.1.0
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
					'cert': cert
				}			
				$.post(ajaxurl, data, function(response){
					// console.log(response);
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
 * Menu di navigazione della pagina opzioni
 */
var wccd_menu_navigation = function() {
	jQuery(function($){
		var $contents = $('.wccd-admin')
		var url = window.location.href.split("#")[0];
		var hash = window.location.href.split("#")[1];

		if(hash) {
	        $contents.hide();		    
		    $('#' + hash).fadeIn(200);		
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
	        
	        $contents.hide();
	        $("#" + $this.data("link")).fadeIn(200);
	        $('h2#wccd-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
	        $this.addClass('nav-tab-active');

	        window.location = url + '#' + $this.data('link');

	        $('html, body').scrollTop(0);

	    })

	})
}
wccd_menu_navigation();

