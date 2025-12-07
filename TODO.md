# TODO - WC Carta Docente e Plugin Correlati

## Struttura Branch e Plugin

**Branch di sviluppo:** `dev-version`
- Tutte le modifiche partono da qui
- Dopo test, si effettua merge nei branch specifici dei plugin

**Plugin derivati:**
1. **Carta del Docente**
   - Branch: `free`, `premium`
   - Versioni attuali: free 1.4.6, premium 1.4.8
   - SVN: https://plugins.svn.wordpress.org/wc-carta-docente/
   - GitHub: https://github.com/ilGhera/wc-carta-docente

2. **18app** (DISMESSO - servizio terminato)
   - Branch: `free-18`, `premium-18`
   - Non più in sviluppo

3. **Carte Cultura** (sostituisce 18app)
   - Branch: `free-cc`, `premium-cc`
   - Versioni da verificare
   - SVN: https://plugins.svn.wordpress.org/wc-carte-cultura/
   - GitHub: Da aggiungere se necessario

4. **Carta della Cultura**
   - Branch: `free-cdc`, `premium-cdc`
   - Versioni da verificare
   - SVN: Da aggiungere quando disponibile
   - GitHub: Da aggiungere quando disponibile

---

## Workflow Release

### 1. Sviluppo
1. Implementa fix/feature in `dev-version`
2. Test e commit in `dev-version`
3. Merge in `free` e `premium` del plugin principale (Carta del Docente)
4. Merge negli altri plugin correlati (CC, CDC) se necessario

### 2. Release Versione Free (WordPress.org)

**Riferimento:** `~/Dropbox/wp-repositories/RELEASE-PROCESS.md`

Passaggi principali:
1. Checkout branch `free` del plugin
2. Verifica versione in readme.txt e file principale
3. Aggiorna repository SVN locale (`svn update`)
4. Copia codice in `~/Dropbox/wp-repositories/[PLUGIN]/tags/X.X.X/`
5. Rimuovi file non necessari: `plugin-update-checker/`, `LICENSE`, `publiccode.yml`, `.git*`, `TODO.md`
6. Aggiungi tag a SVN (`svn add tags/X.X.X`)
7. Aggiorna trunk con `rsync -av --delete tags/X.X.X/ trunk/`
8. Commit SVN con messaggio in INGLESE (`svn ci -m "Release version X.X.X - Description"`)
9. **IMPORTANTE:** Aggiorna `publiccode.yml` nel repository Git:
   - `softwareVersion`: nuova versione
   - `releaseDate`: data corrente (YYYY-MM-DD)
   - Commit: `git commit -m "Update publiccode.yml version X.X.X"`
   - Push: `git push origin free`
10. **IMPORTANTE:** Push su GitHub (solo versioni free):
    - `git push github free:master` (branch free → master GitHub)
    - `git push github free-X.X.X` (tag)

### 3. Release Versione Premium

1. Checkout branch `premium`
2. Verifica versione e changelog
3. Commit e push su Bitbucket
4. Crea tag: `git tag -a premium-X.X.X -m "Release premium version X.X.X"`
5. Push tag: `git push origin premium-X.X.X`

**Note:**
- Le versioni premium NON vanno su WordPress.org
- Le versioni premium NON vanno su GitHub
- Solo Bitbucket per le versioni premium

---

## Bug da Risolvere

### ✅ [RISOLTO PARZIALMENTE] Gestione eccezioni SOAP in modalità sandbox

**File:** `includes/class-wccd-teacher-gateway.php:616, 625`

**Problema:**
Il codice tentava di accedere direttamente a `$e->detail->FaultVoucher->exceptionMessage` senza verificare che queste proprietà esistessero, causando warning PHP "Attempt to read property on null" quando l'eccezione non aveva la struttura SOAP prevista (es. in modalità sandbox).

**Impatto:**
- Gli ordini non venivano creati in modalità sandbox
- Warning PHP nei log
- Esperienza utente degradata

**Soluzione implementata:**
```php
// PRIMA (causava warning)
$output = $e->detail->FaultVoucher->exceptionMessage;

// DOPO (gestione sicura)
$output = isset( $e->detail->FaultVoucher->exceptionMessage )
    ? $e->detail->FaultVoucher->exceptionMessage
    : $e->getMessage();
```

**Stato implementazione:**

| Plugin | Branch | Versione | Stato | Data |
|--------|--------|----------|-------|------|
| Carta del Docente | dev-version | - | ✅ Implementato | 6 Dic 2025 (commit feb2cd7) |
| Carta del Docente | free | 1.4.6 | ✅ Rilasciato | 6 Dic 2025 |
| Carta del Docente | premium | 1.4.8 | ✅ Rilasciato | 6 Dic 2025 |
| Carte Cultura | free-cc | - | ⏳ Da implementare | - |
| Carte Cultura | premium-cc | - | ⏳ Da implementare | - |
| Carta della Cultura | free-cdc | - | ⏳ Da implementare | - |
| Carta della Cultura | premium-cdc | - | ⏳ Da implementare | - |

**Prossimi passi:**
- [ ] Merge fix in branch `free-cc` e `premium-cc` (Carte Cultura)
- [ ] Release e tag versione Carte Cultura (seguire ~/Dropbox/wp-repositories/RELEASE-PROCESS.md)
- [ ] Merge fix in branch `free-cdc` e `premium-cdc` (Carta della Cultura)
- [ ] Release e tag versione Carta della Cultura (seguire ~/Dropbox/wp-repositories/RELEASE-PROCESS.md)

---

### [PRIORITÀ ALTA] Certificati .pem generati NON protetti da password

**File:** `includes/class-wccd-admin.php:808`

**Problema:**
Il plugin genera certificati `.pem` che NON sono protetti da password. La chiave privata viene salvata in chiaro nel file finale.

**Causa:**
Alla linea 808, il certificato finale viene creato concatenando certificato + chiave privata in formato non criptato:
```php
file_put_contents( WCCD_PRIVATE . 'wccd-certificate.pem', $p12['cert'] . $key[0] );
```

La password inserita dall'utente viene usata solo per il file PKCS12 temporaneo (linee 802-805), ma il file `.pem` finale non è protetto.

**Rischi:**
- La chiave privata è leggibile in chiaro sul filesystem
- Se il server viene compromesso, l'attaccante può usare il certificato senza password
- Non è conforme alle best practice di sicurezza SSL/TLS

**Soluzione:**
Criptare la chiave privata nel file finale usando la password dell'utente:

```php
// Linea 808 - PRIMA (NON sicuro)
file_put_contents( WCCD_PRIVATE . 'wccd-certificate.pem', $p12['cert'] . $key[0] );

// DOPO (sicuro - chiave protetta da password)
$encrypted_key = '';
openssl_pkey_export($privkey, $encrypted_key, $wccd_password);
file_put_contents( WCCD_PRIVATE . 'wccd-certificate.pem', $p12['cert'] . $encrypted_key );
```

**Note importanti:**
- I certificati esistenti (non protetti) continueranno a funzionare
- Dopo l'aggiornamento, i NUOVI certificati saranno protetti da password
- La password verrà richiesta quando si usa il certificato per le chiamate SOAP
- **Retrocompatibilità garantita**: il codice che legge il certificato deve gestire entrambi i casi (protetto/non protetto)

**Testing necessario:**
- [ ] Testare generazione nuovo certificato protetto da password
- [ ] Verificare che i certificati vecchi (non protetti) continuino a funzionare
- [ ] Testare chiamate SOAP con certificato protetto
- [ ] Verificare che la password corretta sia richiesta
- [ ] Testare che password errata generi errore appropriato

**Impatto utenti dopo rilascio:**
- ✅ NESSUN problema: i certificati esistenti continueranno a funzionare
- ✅ I nuovi certificati saranno più sicuri
- ✅ Nessuna azione richiesta dagli utenti esistenti

---

### [PRIORITÀ ALTA] Errore caricamento WSDL via SOAP

**File:** `includes/class-wccd-soap-client.php:93`

**Problema Riportato:**
- Ticket da: Giuseppe (giuseppe@codeingenia.it)
- Sito: https://www.accademiadellevante.org
- Errore: `SOAP-ERROR: Parsing WSDL: Couldn't load from 'https://..../VerificaVoucher.wsdl' : failed to load external entity`

**Causa:**
Il file WSDL viene caricato usando un URL pubblico (`WCCD_INCLUDES_URI`) invece del percorso filesystem locale. Questo causa problemi quando:
- Il file .wsdl è bloccato da .htaccess
- Il server ha restrizioni su file XML
- Ci sono problemi SSL/certificati
- Il server blocca richieste loopback

**Soluzione:**
Modificare la riga 93 in `includes/class-wccd-soap-client.php`:

```php
// PRIMA (non funziona)
$this->wsdl = WCCD_INCLUDES_URI . 'VerificaVoucher.wsdl';

// DOPO (usa percorso filesystem)
$this->wsdl = WCCD_INCLUDES . 'VerificaVoucher.wsdl';
```

**Vantaggi della soluzione:**
- Non dipende dal web server
- Non è soggetto a restrizioni .htaccess
- Non richiede che il file sia pubblicamente accessibile
- Più veloce (no richieste HTTP)
- Funziona con qualsiasi configurazione SSL

**Testing necessario:**
- [ ] Testare in ambiente sandbox
- [ ] Testare in ambiente produzione
- [ ] Verificare che funzioni con certificati validi
- [ ] Verificare compatibilità con diverse configurazioni server

---

## Miglioramenti Futuri

### Validazione Certificati Caricati (Versione Free)

**Problema Ricorrente:**
Molti utenti caricano certificati incompleti seguendo la guida di CartaDocente, ricevendo l'errore:
```
SOAP-ERROR: Could not connect to host
```

**Causa:**
Il file .pem caricato contiene solo il certificato pubblico (-----BEGIN CERTIFICATE-----) ma **MANCA la chiave privata** (-----BEGIN PRIVATE KEY-----).

L'autenticazione client SSL/TLS richiede ENTRAMBI:
1. Certificato pubblico (per identificarsi)
2. Chiave privata (per firmare l'handshake SSL)

**Soluzioni da implementare:**
- [ ] Validare il certificato all'upload verificando che contenga sia CERTIFICATE che PRIVATE KEY
- [ ] Mostrare messaggio di errore specifico se la chiave privata è mancante
- [ ] Aggiungere guida/tooltip che spiega come combinare chiave + certificato:
  ```bash
  cat chiave_privata.key certificato_firmato.cer > certificato_completo.pem
  ```
- [ ] Considerare supporto formato PKCS#12 (.p12/.pfx) che include già chiave+certificato

**Benefici:**
- Riduzione ticket di supporto
- Esperienza utente migliore
- Evita incomprensioni sulla differenza free/premium

**Note:**
- Il plugin funziona correttamente: non è una limitazione voluta
- CartaDocente richiede chiave+certificato (standard SSL/TLS mutual authentication)
- Un certificato valido generato esternamente FUNZIONA se completo

---

### Gestione errori SOAP
- Aggiungere try-catch più dettagliati nelle chiamate SOAP
- Log degli errori più descrittivi per debug
- Messaggi di errore user-friendly

### Sicurezza
- Rivedere l'uso di `verify_peer => false` (riga 140)
- Valutare alternative più sicure per gestire certificati SSL

### Performance
- Valutare caching del WSDL (attualmente WSDL_CACHE_NONE)

---

## Note per Sviluppatori

**Costanti principali:**
- `WCCD_DIR`: percorso filesystem del plugin
- `WCCD_INCLUDES`: percorso filesystem cartella includes/
- `WCCD_INCLUDES_URI`: URL pubblico cartella includes/
- `WCCD_PRIVATE`: percorso cartella certificati privati

**File critici:**
- `includes/class-wccd-soap-client.php`: gestione chiamate SOAP
- `includes/VerificaVoucher.wsdl`: definizione servizio web
- `publiccode.yml`: metadata per developers.italia.it (solo versioni free)

**Repository:**
- **Git principale (Bitbucket):** Tutti i branch (dev-version, free, premium, free-cc, etc.)
- **GitHub:** Solo versioni free (branch master riceve push da free)
- **SVN (WordPress.org):** Solo versioni free
