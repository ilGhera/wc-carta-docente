=== ilGhera Carta Docente for WooCommerce ===
Contributors: ghera74
Tags: WooCommerce, payment gateway, Carta Docente, Carte Cultura, 18app
Stable tag: 1.4.6
Requires at least: 4.0
Tested up to: 6.9
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

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
* Generazione richiesta certificato (.der) (Premium)
* Generazione certificato (.pem) (Premium)

https://youtu.be/CU00ZZBZplI

== Installation ==

= Dalla Bacheca di Wordpress =

* Vai in  Plugin > Aggiungi nuovo.
* Cerca ilGhera Carta Docente for WooCommerce e scaricalo.
* Attiva ilGhera Carta Docente for Woocommerce dalla pagina dei Plugin.
* Una volta attivato, vai in <strong>WooCommerce/ Carta Docente for WC</strong> e imposta le tue preferenze.

= Da WordPress.org =

* Scarica ilGhera Carta Docente for WooCommerce
* Carica la cartella wc-carta-docente su /wp-content/plugins/ utilizzando il tuo metodo preferito (ftp, sftp, scp, ecc...)
* Attiva ilGhera Carta Docente for WooCommerce dalla pagina dei Plugin.
* Una volta attivato, vai in <strong>WooCommerce/ Carta Docente for WC</strong> e imposta le tue preferenze.


== Screenshots ==
1. Carica il tuo certificato
2. Genera il file .der per richiedere il tuo certificato (Premium)
3. Crea il tuo certificato (Premium)
4. Imposta le categorie dei prodotti acquistabili
5. Metodo di pagamento in pagina di checkout

== Changelog ==

= 1.4.6 =
Data di rilascio: 6 Dicembre, 2025

    * Correzione bug: Gestione eccezioni SOAP in modalità sandbox

= 1.4.5 =
Data di rilascio: 29 Settembre, 2025

    * Implementazione: Cambio nome plugin
    * Implementazione: Nuove immagini
    * Update: Supporto WC 10

= 1.4.4 =
Data di rilascio: 21 Maggio, 2025

    * Update: Supporto WP 6.8
    * Update: Supporto WC 9.5
    * Implementazione: WordPress Coding standards

= 1.4.3 =
Data di rilascio: 24 Ottobre, 2024

    * Correzione bug : Ordine con buono inferiore al valore a carrello non interrotto. 

= 1.4.2 =
Data di rilascio: 22 Ottobre, 2024

    * Implementazione: WSDL cache none 

= 1.4.1 =
Data di rilascio: 22 Ottobre, 2024

    * Implementazione: Nuovo file WSDL 
    * Implementazione: Rimosso metodo check(2) no più supportato da CdD 

= 1.4.0 =
Data di rilascio: 22 Gennaio, 2024

    * Implementazione: Supporto WooCommerce HPOS 
    * Implementazione: (Premium) Opzione esclusione spese di spedizione dal pagamento 
    * Correzione bug: Creation of dynamic property deprecated in PHP 8.2

= 1.3.1 =
Data di rilascio: 2 Maggio, 2023

    Bug: Mancato sistema di traduzione in nome e descrizione metodo di pagamento
    Bug: Richiamo file JS non necessario in versione free
    Bug: Rimossa libreria non necessaria in versione free

= 1.3.0 =
Data di rilascio: 15 Aprile, 2023

    * Implementazione: (Premium) Opzione ordini in sospeso 
    * Implementazione: (Premium) Opzione personalizzazione email per ordini completati manualmente
    * Implementazione: (Premium) Opzione personalizzazione email per ordini rifiutati per buono non valido
    * Implementazione: WordPress Coding standards
    * Update: POT file
    * Bug: (Premium) Buono docente non presente in dettagli ordine in caso di trasformazione in voucher
    * Bug: (Premium) Metodo di pagamento disponibile dopo voucher applicato in modalità sandbox

= 1.2.2 =
Data di rilascio: 24 Ottobre, 2022

    * Update: Nuovo certificato per funzionalità Sandbox 

= 1.2.1 =
Data di rilascio: 23 Giugno, 2022

    * Correzione bug: Errore controllo abbinamenti Categorie/ Beni Carta del Docente

= 1.2.0 =
Data di rilascio: 2 Giugno, 2022

    * Implementazione: Nuova funzionalità sandbox
    * Implementazione: (Premium) Mostra metodo di pagamento solo se consentito dai prodotti presenti a carrello
    * Correzione bug: Codice Carta del Docente mancante in email di conferma d'ordine

= 1.1.1 =
Data di rilascio: 2 Aprile, 2022

    * Correzione bug: Mancato salvataggio singolo abbinamento categoria WP/ Bene Carta del Docente

= 1.1.0 =
Data di rilascio: 22 Maggio, 2021

    * Implementazione: (Premium) Opzione di conversione buono Carta del Docente in codice sconto applicato a carrello nel caso il valore del buono non sia inferiore al totale a carrello
    * Implementazione: Interfaccia migliorata. 

= 1.0.3 =
Data di rilascio: 10 Febbraio, 2020

    * Correzione bug: Categorie impostabili limitate

= 1.0.2 =
Data di rilascio: 09 Novembre, 2019

    * Correzione bug: Denominazione ambito "Libri e testi (anche in formato digitale)" errata.

= 1.0.1 =
Data di rilascio: 02 Ottobre, 2019

    * Implementazione: Possibilità di abbinare differenti categorie WooCommeerce allo stesso "bene" Carta del Docente .
    * Correzione bug: Categorie beni Carta del Docente mancanti.

= 1.0.0 =
Data di rilascio: 27 Giugno, 2019

    * Correzione bug: SOAP-ERROR: Parsing WSDL: Couldn't load from .../wp-content/plugins/wc-carta-docente/includes/VerificaVoucher.wsdl' : failed to load external entity .../wp-content/plugins/wc-carta-docente/includes/VerificaVoucher.wsdl

= 0.9.3 =
Data di rilascio: 8 Novembre, 2018

    * Implementazione: Possibilità di abbinare differenti "beni" Carta del Docente alla stessa categoria WooCommeerce.
    * Implementazione: Aggiornata gamma "beni" disponibili.

= 0.9.2 =
Data di rilascio: 27 Agosto, 2018

    * Correzione bug: Richiesta password utilizzata per creare il certificato caricato dall'utente.

= 0.9.1 =
Data di rilascio: 27 Agosto, 2018

    * Implementazione: Attivazione certificato come richiesto dalla piattaforma Carta del Docente.
    * Implementazione: Attivazione del sistema di pagamento solo ad attivazione certificato completata.
    * Correzione bug: Errato path certificato in istanza classe wccd_soap_client.

= 0.9.0 =
Data di rilascio: 2 Luglio, 2018

    * Prima release.
