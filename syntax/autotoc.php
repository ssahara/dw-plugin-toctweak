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
                if (count($matches) == 4) {
                    if ($matches[3] > 0) {
                        $topLv = $matches[3];
                    } else {
                        $maxLv = abs($matches[3]);
                    }
                } else {
                        $topLv = $matches[1];
                        $maxLv = $matches[2];
                }
                if (isset($topLv)) {
                    $topLv = ($topLv < 1) ? 1 : $topLv;
                    $topLv = ($topLv > 5) ? 5 : $topLv;
                }
                if (isset($maxLv)) {
                    $maxLv = ($maxLv > 5) ? 5 : $maxLv;
                }
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
        global $ID, $conf;

        list($id, $topLv, $maxLv, $tocClass) = $data;

        // skip calls that belong to different page (eg. included pages)
        if ($id != $ID) return false;

        switch ($format) {
            case 'xhtml':
                // force set $conf
                if (isset($topLv)) {
                    $conf['toptoclevel'] = $topLv;
                }
                if (isset($maxLv)) {
                    $conf['maxtoclevel'] = $maxLv;
                }
                return true;

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
