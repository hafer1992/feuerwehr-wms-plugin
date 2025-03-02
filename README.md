# feuerwehr-wms-plugin
Plugin für [DokuWiki](https://www.dokuwiki.org/) um einzelne Seiten eines externen DokuWikis einzubinden.
Das Plugin ist noch in Entwicklung. Benutzung auf eigene Gefahr und Verantwortung.

In diesem Repository findet sich eine erste Version des Plugins, um externe Seiten eines DokuWikis in ein anderes zu inkludieren.

Syntax zum Importieren: `{{extern>wikiname§page:with:namespaces}}` 

 - `extern`: triggers the parser to activate the includeextern Plugin
 - `wikiname`: needs to be added to the config file to resolve the URL
 - `page:with:namespaces`: relative link to the page that should be included
