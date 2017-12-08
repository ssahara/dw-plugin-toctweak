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

        // strip and split markup
        $params = explode(' ', substr($match, strpos($this->pattern[5],':')+1, -2));

        foreach ($params as $token) {
            if (empty($token)) continue;

            // get TOC generation parameters
            if (preg_match('/^(?:(\d+)-(\d+)|^(\-?\d+))$/', $token, $matches)) {
                if (count($matches) == 4) {
                    if (strpos($matches[3], '-') !== false) {
                        $maxLv = abs($matches[3]);
                    } else {
                        $topLv = $matches[3];
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

        // get where and how the TOC should be located in the page
        // -1: PLACEHOLDER set by syntax component
        //  0: default. TOC will not moved (tocPostion config option)
        //  1: set PLACEHOLDER after the first heading (tocPosition config option)
        //  2: set PLACEHOLDER after the first level 1 heading (tocPosition config optipn)
        $tocPosition = -1;

        switch ($format) {
            case 'xhtml':
                // Add PLACEHOLDER to cached page (will be replaced by action component)
                $lv['top'] = (isset($topLv))
                    ? max($conf['plugin']['toctweak']['_toptoclevel'], $topLv)
                    : $conf['plugin']['toctweak']['_toptoclevel'];
                $lv['max'] = (isset($maxLv))
                    ? min($conf['plugin']['toctweak']['_maxtoclevel'], $maxLv)
                    : $conf['plugin']['toctweak']['_maxtoclevel'];

/* ---------------------------------------------------
                $lv['top'] = (isset($topLv))
                    ? max($conf['toptoclevel'], $topLv)
                    : $conf['toptoclevel'];
                $lv['max'] = (isset($maxLv))
                    ? min($conf['maxtoclevel'], $maxLv)
                    : $conf['maxtoclevel'];
--------------------------------------------------- */

                $placeHolder = '<!-- '.strstr(substr($this->pattern[5],2),':',1)
                              .' '.$lv['top'].' '.$lv['max'].' '.$tocClass
                              .' -->';
                $renderer->doc .= $placeHolder . DOKU_LF;
                error_log('movetoc '.$placeHolder);
                return true;

            case 'metadata':
                $renderer->meta['toc']['position'] = $tocPosition;
                return true;
        }
        return false;
    }

}
