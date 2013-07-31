Creare la directory pages nella root della webapp.

Es. webapp/pages

All'interno immagazzinare, anche eventualmente con subdirectory, le pagine
del sito da utilizzare.

Le pagine vengono richiamate quando è presente il modulo content aggiungendo
nella query il parametro "content_page".

Es. "?content_page=home/index.html"

Di base prevede l'esistenza della pagina "common/404.html", che viene
richiamata quando si richiede una pagina non esistente. Un controllo di
sicurezza previene il richiamo di pagine al di fuori del contesto della
directory delle pagine.

Supporta il linguaggio di default come chiave di contesto
"portalserverDefaultLanguage" in web.xml.

Esempio:

    <contextparam>
		<paramname>portalserverDefaultLanguage</paramname>
		<paramvalue>it</paramvalue>
    </contextparam>
