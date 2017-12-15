<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak autotoc
 * set top and max level of headings of the page with optional css class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class syntax_plugin_toctweak_autotoc extends DokuWiki_Syntax_Plugin {

    protected $mode;
    protected $pattern = array(
        5 => '~~TOC:?.*?~~',  // DOKU_LEXER_SPECIAL
    );

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

        // load helper object
        $helper = $helper ?: $this->loadHelper($this->getPluginName());

        // strip markup
        $start = strpos($this->pattern[5],':');
        $param = substr($match, $start+1, -2);
        list($topLv, $maxLv, $tocClass) = $helper->parse($param);

        return $data = array($ID, $topLv, $maxLv, $tocClass);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        global $ID, $conf;

        list($id, $topLv, $maxLv, $tocClass) = $data;

        // skip calls that belong to different page (eg. included pages)
        if ($id != $ID) return false;

        switch ($format) {
            case 'metadata':
                // store matadata to overwrite $conf in PARSER_CACHE_USE event handler
                if (isset($topLv)) {
                    $renderer->meta['toc']['toptoclevel'] = $topLv;
                }
                if (isset($maxLv)) {
                    $renderer->meta['toc']['maxtoclevel'] = $maxLv;
                }
                if (isset($tocClass)) {
                    $renderer->meta['toc']['class'] = $tocClass;
                }
                return true;
        }

        return false;
    }

}
