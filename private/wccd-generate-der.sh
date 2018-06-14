#! /bin/bash

#Passa a cartella Private
cd ../wp-content/plugins/wc-carta-docente/private

#Controllo presenza di OpenSSL
if ! [ -x "$(command -v openssl)" ]
	then apt install openssl 
fi

#Creazione file .der per richiesta certificato
openssl req \
	-newkey rsa:2048 \
	-keyout files/key.der \
	-passout pass:"fullgas" -subj "/C=IT/ST=Italia/" \
	-out files/certificate-request.der \
	-outform DER

chmod 777 files/key.der 
chmod 777 files/certificate-request.der