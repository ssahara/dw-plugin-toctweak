<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak autotoc
 * set top and max level of headlines to be found in table of contents
 * render toc placeholder to show built-in toc box in the page
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class syntax_plugin_toctweak_autotoc extends DokuWiki_Syntax_Plugin {

    protected $mode;
    protected $pattern = array(
        5 => '~~(?:TOC(?:_HERE)?)\b.*?~~',
    );

    const TOC_HERE = '<!-- TOC_HERE -->';

    function __construct() {
        $this->mode = substr(get_class($this), 7); // drop 'syntax_' from class name
    }

    function getType() { return 'substition'; }
    function getPType(){ return 'block'; }
    function getSort() { return 30; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->pattern[5], $mode, $this->mode);
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        global $ID;
        static $call_counter = [];  // holds number of ~~TOC_HERE~~ used in the page

        // load helper object
        isset($tocTweak) || $tocTweak = $this->loadHelper($this->getPluginName());

        // parse syntax
        if (strpos($match, 'TOC_HERE') !== false) {
            // ignore ~~TOC_HERE~~ macro appeared more than once in a page
            if ($call_counter[$ID]++ > 0) return;
            $param = substr($match, 11, -2);
            $tocPosition = -1;
        } else {
            $param = substr($match, 6, -2);
            $tocPosition = null;
        }
        list($topLv, $maxLv, $tocClass) = $tocTweak->parse($param);

        return $data = array($ID, $tocPosition, $topLv, $maxLv, $tocClass);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        global $ID;

        list($id, $tocPosition, $topLv, $maxLv, $tocClass) = $data;

        // skip calls that belong to different page (eg. included pages)
        if ($id != $ID) return false;

        switch ($format) {
            case 'metadata':
                // store matadata to overwrite $conf in PARSER_CACHE_USE event handler
                isset($tocPosition) && $renderer->meta['toc']['position'] = $tocPosition;
                isset($topLv)       && $renderer->meta['toc']['toptoclevel'] = $topLv;
                isset($maxLv)       && $renderer->meta['toc']['maxtoclevel'] = $maxLv;
                isset($tocClass)    && $renderer->meta['toc']['class'] = $tocClass;
                return true;

            case 'xhtml':
                // render PLACEHOLDER, which will be replaced later
                // through action event handler handleContentDisplay()
                if (isset($tocPosition)) {
                    $renderer->doc .= self::TOC_HERE.DOKU_LF;
                    return true;
                }
        } // end of switch
        return false;
    }

}
