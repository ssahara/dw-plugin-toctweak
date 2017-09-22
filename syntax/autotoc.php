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

        $params = explode(' ', substr($match, 6, -2));

        foreach ($params as $token) {
            if (empty($token)) continue;

            // get TOC generation parameters
            if (preg_match('/^(?:(\d+)-(\d+)|^(\-?\d+))$/', $token, $matches)) {
                $topLv = $matches[1] ?: $matches[3];
                $maxLv = $matches[2] ?: $matches[3];
                continue;
            }

            // get class name for TOC box, ensure excluded any malcious character
            if (!preg_match('/[^ A-Za-z0-9_-]/', $token)) {
                $classes[] = $token;
            }
        }
        if (!empty($classes)) {
            $tocClass = implode(' ', $classes);
        }

        return $data = array($ID, $topLv, $maxLv, $tocClass);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        global $ID;

        list($id, $topLv, $maxLv, $tocClass) = $data;

        // skip calls that belong to different page (eg. included pages)
        if (($format == 'metadata') && ($id == $ID)) {

            // store matadata to overwrite $conf in PARSER_CACHE_USE event handler
            if (isset($topLv)) {
                if ($topLv == 0) $topLv = 1;
                $topLv = ($topLv > 5) ? 5 : $topLv;
                $renderer->meta['toc']['toptoclevel'] = $topLv;
            }
            if (isset($maxLv)) {
                $maxLv = ($maxLv > 5) ? 5 : $maxLv;
                $renderer->meta['toc']['maxtoclevel'] = $maxLv;
            }
            if (isset($tocClass)) {
                $renderer->meta['toc']['class'] = $tocClass;
            }
            return true;
        } else {
            return false;
        }
    }

}
