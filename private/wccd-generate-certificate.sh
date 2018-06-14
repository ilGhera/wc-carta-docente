#! /bin/bash

#Passa a cartella Private
cd ../wp-content/plugins/wc-carta-docente/private

#Identifica file .cer
cer_files=( *.cer )

#Creaione file .pem da .cert fornito da Carta del docente
# openssl x509 -inform der -in ${cer_files[0]} -out files/wccd-cert.pem

#Cambio permessi
# chmod 777 files/wccd-cert.pem

#Genero file .nrd 
touch files/.rnd

#Cambio permessi
chmod 777 files/.rnd
# chown user:user files/.rnd

export RANDFILE="files/.rnd"

#Creazione file p12
openssl pkcs12 -export \
	-inkey files/key.der \
	-passin pass:"fullgas" \
	-passout pass:"fullgas" \
	-in files/wccd-cert.pem \
	-out files/wccd-cert.p12

#Cambio permessi
chmod 777 files/wccd-cert.p12

#Creazione del certificato
openssl pkcs12 \
	-in files/wccd-cert.p12 \
	-passin pass:"fullgas" \
	-passout pass:"fullgas" \
	-out wccd-certificate.pem \
	-clcerts

#Cambio permessi
chmod 777 wccd-certificate.pem