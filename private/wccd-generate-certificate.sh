#!/bin/bash

#Passa a cartella Private
# cd ../wp-content/plugins/wc-carta-docente/private

#Identifica file .cer
files=( *.cer )

#Creaione file .pem da .cert fornito da Carta del docente
openssl x509 -inform der -in ${files[0]} -out wccd-cert.pem

#Creazione file p12
openssl pkcs12 -export -inkey key.der -in wccd-cert.pem -out wccd-cert.p12