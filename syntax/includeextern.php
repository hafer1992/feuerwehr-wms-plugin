<?php
/**
 * IncludeExtern Plugin: displays a wiki page within another
 * Usage:
 * {{extern>wikiname§page}} e.g. {{extern>elwiki§cbrn:biologisch:biogasanlage}}
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Schultz
 */

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_includeextern_includeextern extends DokuWiki_Syntax_Plugin {


    /** @var $helper helper_plugin_includeextern */
    var $helper = null;

    /**
     * Get syntax plugin type.
     *
     * @return string The plugin type.
     */
    function getType() { return 'substition'; }

    /**
     * Get sort order of syntax plugin.
     *
     * @return int The sort order.
     */
    function getSort() { return 303; }

    /**
     * Get paragraph type.
     *
     * @return string The paragraph type.
     */
    function getPType() { return 'block'; }

    /**
     * Connect patterns/modes
     *
     * @param $mode mixed The current mode
     */
    function connectTo($mode) { 
        $this->Lexer->addSpecialPattern("{{extern>.+?}}", $mode, 'plugin_includeextern_includeextern');
        
    }

    /**
     * Handle syntax matches
     *
     * @param string       $match   The current match
     * @param int          $state   The match state
     * @param int          $pos     The position of the match
     * @param Doku_Handler $handler The hanlder object
     * @return array The instructions of the plugin
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {

        $match = substr($match, 2, -2); // strip markup {{ and }}

        list($match, $flags) = array_pad(explode('§', $match, 2), 2, '');
        
        // break the pattern up into its parts
        list($mode, $site) = array_pad(preg_split('/>|#/u', $match, 2), 2, null);
        
        return array($mode, $site, explode('&', $flags));
    }

    /**
     * Renders the included page(s)
     *
     */
    function render($format, Doku_Renderer $renderer, $data) {
        global $ID;

            list($mode, $site, $flags) = $data;
        
            $sites = $this->getConf('ie-sites');
        
        if(isset($sites[$site])) {
            $wiki_url = $sites[$site]['url'];
        } else {
            $renderer->doc .= "<pre>Es konnte keine externe Seite mit dem Code " . $site . "gefunden werden.</pre>";
           return true;
        }
            
            // JSON-RPC Methode, die aufgerufen werden soll
            $method = "core.getPageHTML";

            // Parameter für die Methode (z.B. Seiten-ID)
            $page_id = $flags[0];

            // JSON-RPC Anfrage Payload erstellen
            $request = json_encode([
                'jsonrpc' => '2.0',
                'method'  => $method,
                'params'  => [$page_id],
                'id'      => 1
            ]);

            // cURL-Session initialisieren
            $ch = curl_init();

            // cURL Optionen setzen
            curl_setopt($ch, CURLOPT_URL, $wiki_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // Antwort als String zurückgeben
            curl_setopt($ch, CURLOPT_POST, 1);            // POST Anfrage senden
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request); // JSON-RPC Payload als POST-Daten

            // HTTP Basic Authentication hinzufügen (für DokuWiki-Login)
            //curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',  // JSON Content-Type setzen
                'Content-Length: ' . strlen($request)
            ]);

            // Anfrage ausführen und Antwort erhalten
            $response = curl_exec($ch);

            // Prüfen, ob cURL-Fehler aufgetreten sind
            if (curl_errno($ch)) {
                echo 'cURL Fehler: ' . curl_error($ch);
            } else {
                // Antwort decodieren
                $decoded_response = json_decode($response, true);
                // Prüfen, ob die JSON-RPC-Anfrage einen Fehler zurückgab
                if (isset($decoded_response['error'])) {
                    echo "JSON-RPC Fehler: " . $decoded_response['error']['message'];
                } else {
                    // Erfolgreich: Den Inhalt der Seite ausgeben

                    //TODO: In den eingebundene Texten müssen noch ein paar Sachen angepasst werden (z.B. keine Edit-Buttons)
                    //$decoded_response['result'] = str_replace("button btn_incledit", "\" style111=\"display:none", $decoded_response['result']);

                    $renderer->doc .= $decoded_response['result'];
                }
            }
            // cURL-Session schließen
            curl_close($ch);
        
            return true;
        }


    
}
