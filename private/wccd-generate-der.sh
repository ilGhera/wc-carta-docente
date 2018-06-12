#!/bin/bash

#Passa a cartella Private
cd ../wp-content/plugins/wc-carta-docente/private

#Controllo presenza di OpenSSL
if ! [ -x "$(command -v openssl)" ]
	then apt install openssl 
fi

#Creazione file .der per richiesta certificato
openssl req \
	-newkey rsa:2048 \
	-keyout key.der \
	-passout pass:"test" -subj "/C=IT/ST=Italia/" \
	-out certificate-request.der \
	-outform DER

chmod 777 key.der certificate-request.der