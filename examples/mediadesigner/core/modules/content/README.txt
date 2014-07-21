Creare la directory content nella cartella core della webapp.

Es. webapp/core/content

Creare la struttura delle pagine, anche divise in sottocartelle.

Le pagine sono lette quando è presente il modulo content aggiungendo
nella query il parametro "content_page".

Es. "?content_page=home/index.html"

E' prevista di base l'esistenza della pagina "common/404.html", che viene
richiamata quando si richiede una pagina non esistente. Un controllo di
sicurezza previene il richiamo di pagine al di fuori del contesto della
directory delle pagine.

Supporta il linguaggio di default come chiave di contesto
"InnomediaDefaultLanguage" in web.xml.

Esempio:

    <contextparam>
		<paramname>InnomediaDefaultLanguage</paramname>
		<paramvalue>it</paramvalue>
    </contextparam>
