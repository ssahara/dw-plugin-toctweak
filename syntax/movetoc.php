<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak movetoc
 * move toc position in the page with optional css class
 * set top and max level of headings of the page with optional css class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class syntax_plugin_toctweak_movetoc extends DokuWiki_Syntax_Plugin {

    protected $mode;
    protected $pattern = array(
        5 => '{{TOC:?.*?}}',  // DOKU_LEXER_SPECIAL
    );
    protected $place_holder = '<!-- TOC -->';

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
        return array($state, $match);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $indata) {
        if (empty($indata)) return false;
        list($state, $data) = $indata;

        // get where and how the TOC should be located in the page
        // -1: PLACEHOLDER set by syntax component
        //  0: default. TOC will not moved (tocPostion config option)
        //  1: set PLACEHOLDER after the first heading (tocPosition config option)
        //  2: set PLACEHOLDER after the first level 1 heading (tocPosition config optipn)
        $tocPosition = (substr($data, 0, 2) == '{{') ? -1 : 0;

        if ($format == 'xhtml') {
            // Add PLACEHOLDER to cached page (will be replaced by action component)
            if ($tocPosition < 0) $renderer->doc .= $this->place_holder;
            return true;

        } elseif ($format == 'metadata') {
            // strip and split markup
            $matches = preg_split('/[:\s]+/', substr($data, 2, -2), 2);
            $match = $matches[1];

            // get TOC generation parameter
            if (preg_match('/\b(?:(\d+)?-(\d+)|(\d+))\b/', $match, $matches)) {
                if (count($matches) == 4) {
                    $topLv = $matches[3];
                } else {
                    $topLv = $matches[1];
                    $maxLv = $matches[2];
                }
                $match = preg_replace('/\b'.$matches[0].'\b/', '', $match);
            }

            // get class name for TOC box, ensure excluded any malcious character
            if (!preg_match('/[^ A-Za-z0-9_-]/', $match)) {
                $tocClass = trim($match);
            }

            if ($tocPosition != 0) {
                $renderer->meta['toc']['position'] = $tocPosition;
            }
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
