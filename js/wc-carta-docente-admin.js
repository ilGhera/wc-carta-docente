/**
 * WC Carta Docente - Admin js
 */

/**
 * Ajax - Elimina il certificato caricato precedentemente
 */
var delete_certificate = function() {
	jQuery(function($){
		$('.delete-certificate').on('click', function(){
			var sure = confirm('Sei sicuro di voler eliminare il certificato?');
			if(sure) {
				var cert = $('.cert-loaded').text();
				var data = {
					'action': 'delete-certificate',
					'delete': true,
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
delete_certificate();


/**
 * Aggiunge un nuovo abbinamento bene/ categoria per il controllo in pagina di checkout
 */
var add_cat = function() {
	jQuery(function($){
		$('.add-cat-hover').on('click', function(){
			var number = $('.setup-cat').length + 1;
			var limit = $('.wccd-field.beni:first option').size() -1;

			/*Beni già impostati da escludere*/
			var beni_values = [];
			$('.wccd-field.beni').each(function(){
				beni_values.push($(this).val());
			})

			/*Categorie già utilizzate da escludere*/
			var cats_values = [];
			$('.wccd-field.categories').each(function(){
				cats_values.push($(this).val());
			})

			/*Se assegnate tutte le categorie visualizza messaggio*/
			if(number > limit) {
				alert('Tutte le categorie di prodotto sono state assegnate.');
			} else {
				
				var data = {
					'action': 'add-cat',
					'number': number,
					'exclude-beni': beni_values.toString(),
					'exclude-cats': cats_values.toString()
				}
				$.post(ajaxurl, data, function(response){
					$(response).appendTo('.categories-container');
					$('.wccd-tot-cats').val(number);
				})				
			}
		})
	})
}
add_cat();


/**
 * Rimuove un abbinamento bene/ categoria
 */
var remove_cat = function() {
	jQuery(function($){
		$(document).on('click', '.remove-cat-hover', function(response){
			var cat = $(this).closest('li');
			$(cat).remove();
			var number = $('.setup-cat').length;
			$('.wccd-tot-cats').val(number);
		})
	})
}
remove_cat();


/**
 * Genera e fa scaricare all'utente il file .der
 */
var generate_der = function() {
	jQuery(function($){
		// $('.generate-der').on('click', function(){
		// 	var data = {
		// 		'action': 'generate-der',
		// 		'generate-der': 1
		// 	}
		// 	$.post(ajaxurl, data, function(response){
		// 		console.log(response);
		// 	})
		// })
	})
}
generate_der();