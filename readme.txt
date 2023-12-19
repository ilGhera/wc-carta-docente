=== WooCommerce Carta Docente - Premium ===
Contributors: ghera74
Tags: Woocommerce, e-commerce, shop, orders, payment, payment gateway, payment method, 
Version: 1.4.0
Requires at least: 4.0
Tested up to: 6.4

Abilita in WooCommerce il pagamento con Carta del Docente.

== Description ==

Il plugin consente di abilitare sul proprio store il pagamento con Carta del Docente.
In fase di checkout, il buono inserito dall'utente verrà verificato per validità, credito disponibile e pertinenza in termini di tipologia di prodotto.


= Nore importanti =
Il plugin prevede l'invio di contenuti ad un servizio esterno, in particolare i dati relativi ai prodotti acquistati dall'utente come categoria d'appartenenza e prezzo.

= Indirizzo di destinazione =
[https://ws.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher](https://ws.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher)

= Maggiori informazioni sul servizio Carta del docente: =
[https://cartadeldocente.istruzione.it/](https://cartadeldocente.istruzione.it/)

= Informativa privacy del servizio: =
[https://cartadeldocente.istruzione.it/static/cartadeldocente_infoprivacy.pdf](https://cartadeldocente.istruzione.it/static/cartadeldocente_infoprivacy.pdf)


= Important notes =
This plugin sends data to an external service, like the categories and the prices of the products bought by the user.

= Service endpoint: =
[https://ws.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher](https://ws.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher)

= Service informations: =
[https://cartadeldocente.istruzione.it/](https://cartadeldocente.istruzione.it/)

= Service privacy policy: =
[https://cartadeldocente.istruzione.it/static/cartadeldocente_infoprivacy.pdf](https://cartadeldocente.istruzione.it/static/cartadeldocente_infoprivacy.pdf)


= Funzionalità =

* Caricamento certificato (.pem)
* Impostazione categorie prodotti WooCommerce acquistabili
* Generazione richiesta certificato (.der)
* Generazione certificato (.pem)


== Installation ==

= Dalla Bacheca di Wordpress =

* Vai in  Plugin > Aggiungi nuovo.
* Cerca WooCommerce Carta Docente e scaricalo.
* Attiva Woocommerce Carta Docente dalla pagina dei Plugin.
* Una volta attivato, vai in <strong>WooCommerce/ WC Carta Docente</strong> e imposta le tue preferenze.

= Da WordPress.org =

* Scarica WooCommerce Carta Docente
* Carica la cartella wc-carta-docente su /wp-content/plugins/ utilizzando il tuo metodo preferito (ftp, sftp, scp, ecc...)
* Attiva WooCommerce Carta Docente dalla pagina dei Plugin.
* Una volta attivato, vai in <strong>WooCommerce/ WC Carta Docente</strong> e imposta le tue preferenze.


== Changelog ==

= 1.4.0 =
Data di rilascio: 19 Dicembre, 2023

    * Implementazione: Opzione esclusione spese di spedizione dal pagamento 
    * Implementazione: Supporto WooCommerce HPOS 
    * Correzione bug: Creation of dynamic property deprecated in PHP 8.2


= 1.3.1 =
Data di rilascio: 10 Agosto, 2023

    * Implementazione: Utilizzo tag HTML in descrizione metodo di pagamento
    * Implementazione: Supporto WordPress 6.3 


= 1.3.0 =
Data di rilascio: 6 Aprile, 2023

    * Implementazione: Opzione ordini in sospeso 
    * Implementazione: Opzione personalizzazione email per ordini completati manualmente
    * Implementazione: Opzione personalizzazione email per ordini rifiutati per buono non valido
    * Implementazione: WordPress Coding standards
    * Update: POT file
    * Correzione bug: Buono docente non presente in dettagli ordine in caso di trasformazione in voucher
    * Correzione bug: Metodo di pagamento disponibile dopo voucher applicato in modalità sandbox


= 1.2.5 =
Data di rilascio: 15 Febbraio, 2023

    * Implementazione: Notifica admin chiave di licenza 
    * Update: Plugin Update Checker
    * Update: POT file


= 1.2.4 =
Data di rilascio: 7 Novembre, 2022

    * Implementazione: Supporto WordPress 6.1
    * Update: Plugin Update Checker

= 1.2.3 =
Data di rilascio: 24 Ottobre, 2022

    * Update: Nuovo certificato per funzionalità Sandbox 

= 1.2.2 =
Data di rilascio: 22 Giugno, 2022

    * Correzione bug: Errore controllo abbinamenti Categorie/ Beni Carta del Docente

= 1.2.1 =
Data di rilascio: 16 Giugno, 2022

    * Correzione bug: Errore in array_intersect con opzione controllo prodotti a carrello attiva

= 1.2.0 =
Data di rilascio: 1 Giugno, 2022

    * Implementazione: Nuova funzionalità sandbox
    * Implementazione: Mostra metodo di pagamento solo se consentito dai prodotti presenti a carrello
    * Correzione bug: Codice Carta del Docente mancante in email di conferma d'ordine

= 1.1.2 =
Data di rilascio: 4 Aprile, 2022

    * Correzione bug: Possibile mancato salvataggio singolo abbinamento Categoria/ Bene Carta del Docente 

= 1.1.1 =
Data di rilascio: 22 Maggio, 2021

    * Correzione bug: Mancata eliminazione ordine temporaneo in caso di conversione buono Carta del Docente in codice sconto

= 1.1.0 =
Data di rilascio: 20 Maggio, 2021

    * Implementazione: Opzione di conversione buono Carta del Docente in codice sconto applicato a carrello nel caso in cui il valore del buono sia inferiore al totale a carrello
    * Implementazione: Interfaccia migliorata. 

= 1.0.5 =
Data di rilascio: 28 Aprile, 2020

    * Correzione bug: Impossibile eliminare certificato non funzionante
    * Correzione bug: Errore salvataggio file .der in presenza del plugin WooCommerce 18app - Premium 

= 1.0.4 =
Data di rilascio: 10 Febbraio, 2020

    * Correzione bug: Categorie impostabili limitate

= 1.0.3 =
Data di rilascio: 09 Novembre, 2019

    * Correzione bug: Denominazione ambito "Libri e testi (anche in formato digitale)" errata.

= 1.0.2 =
Data di rilascio: 02 Ottobre, 2019

    * Implementazione: Possibilità di abbinare differenti categorie WooCommeerce allo stesso "bene" Carta del Docente .
    * Correzione bug: Categorie beni Carta del Docente mancanti.

= 1.0.1 =
Data di rilascio: 27 Giugno, 2019

    * Correzione bug: SOAP-ERROR: Parsing WSDL: Couldn't load from .../wp-content/plugins/wc-carta-docente-premium/includes/VerificaVoucher.wsdl' : failed to load external entity .../wp-content/plugins/wc-carta-docente-premium/includes/VerificaVoucher.wsdl

= 1.0.0 =
Data di rilascio: 5 Febbraio, 2019

    * Implementazione: Backup di ogni richiesta certificato generato con relativa chiave
    * Implementazione: Nuova cartella wccd-private in wp uploads directory
    * Correzione bug: Eliminazione contenuto cartella private con aggiornamento 
    * Correzione bug: Mancato salvataggio di un singolo abbinamento di categorie prodotti 

= 0.9.5 =
Data di rilascio: 8 Novembre, 2018

    * Implementazione: Possibilità di abbinare differenti "beni" Carta del Docente alla stessa categoria WooCommeerce.
    * Implementazione: Aggiornata gamma "beni" disponibili.

= 0.9.4 =
Data di rilascio: 18 Ottobre, 2018

    * Correzione bug: Richiesta password utilizzata per creare il certificato caricato dall'utente.

= 0.9.3 =
Data di rilascio: 14 Settembre, 2018

    * Correzione bug: Correzione richiamo password utente in class-wccd-soap-client.php

= 0.9.2 =
Data di rilascio: 14 Settembre, 2018

    * Correzione bug: Errore nella generazione del file wccd-cert.p12

= 0.9.1 =
Data di rilascio: 27 Agosto, 2018

    * Implementazione: Attivazione certificato come richiesto dalla piattaforma Carta del Docente.
    * Implementazione: Attivazione del sistema di pagamento solo ad attivazione certificato completata.
    * Correzione bug: Errato path certificato in istanza classe wccd_soap_client.

= 0.9.0 =
Data di rilascio: 2 Luglio, 2018

    * Prima release.
