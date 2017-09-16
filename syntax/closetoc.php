<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak closetoc
 * set toggle state initially closed (by script.js)
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if (!defined('DOKU_INC')) die();

class syntax_plugin_toctweak_closetoc extends DokuWiki_Syntax_Plugin {

    protected $mode;
    protected $pattern = array();

    function __construct() {
        $this->mode = substr(get_class($this), 7); // drop 'syntax_' from class name

        // syntax patterns
        $this->pattern[5] = '~~CLOSETOC~~'; // DOKU_LEXER_SPECIAL
    }


    function getType() { return 'substition'; }
    function getPType(){ return 'block'; }
    function getSort() { return 990; }

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
        return array($ID);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        global $ID;
        if (($format == 'metadata') && ($data[0] == $ID)) {
             $renderer->meta['toc']['initial_state'] = -1;
        }
        return true;
    }

}

