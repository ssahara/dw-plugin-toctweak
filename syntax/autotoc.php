<?php
/**
 * TocTweak plugin for DokuWiki; Syntax autotoc
 * set top and max level of headlines to be found in table of contents
 * allow to set autoTOC state initially closed
 * render toc placeholder to show built-in toc box in the page
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class syntax_plugin_toctweak_autotoc extends DokuWiki_Syntax_Plugin {

    protected $mode;
    protected $pattern = array(
        5 => '~~(?:TOC_HERE|(?:NO|CLOSE)?TOC)\b.*?~~',
    );

    const TOC_HERE = '<!-- TOC_HERE -->'.DOKU_LF;

    function __construct() {
        $this->mode = substr(get_class($this), 7); // drop 'syntax_' from class name
    }

    function getType() { return 'substition'; }
    function getPType(){ return 'block'; }
    function getSort() { return 29; } // less than Doku_Parser_Mode_notoc = 30

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
        preg_match('/^~~([A-Z_]+)/', $match, $m);
        $start = strlen($m[1]) +2;
        $param = substr($match, $start+1, -2);
        list($topLv, $maxLv, $tocClass) = $tocTweak->parse($param);

        switch ($m[1]) {
            case 'NOTOC':
                $handler->_addCall('notoc', array(), $pos);
                $tocPosition = 9;
                break;
            case 'CLOSETOC':
                $tocPosition = null; // $this->getConf('tocPosition');
                $tocState = -1;
                break;
            case 'TOC':
                $tocPosition = null; // $this->getConf('tocPosition');
                break;
            case 'TOC_HERE':
                // ignore ~~TOC_HERE~~ macro appeared more than once in a page
                if ($call_counter[$ID]++ > 0) return;
                $tocPosition = -1;
                break;
        } // end of switch

        return $data = array($ID, $tocPosition, $tocState, $topLv, $maxLv, $tocClass);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        global $ID;

        list($id, $tocPosition, $tocState, $topLv, $maxLv, $tocClass) = $data;

        // skip calls that belong to different page (eg. included pages)
        if ($id != $ID) return false;

        switch ($format) {
            case 'metadata':
                // store matadata to overwrite $conf in PARSER_CACHE_USE event handler
                isset($tocPosition) && $renderer->meta['toc']['position'] = $tocPosition;
                isset($tocState)    && $renderer->meta['toc']['state'] = $tocState;
                isset($topLv)       && $renderer->meta['toc']['toptoclevel'] = $topLv;
                isset($maxLv)       && $renderer->meta['toc']['maxtoclevel'] = $maxLv;
                isset($tocClass)    && $renderer->meta['toc']['class'] = $tocClass;
                return true;

            case 'xhtml':
                // render PLACEHOLDER if necessary
                return $this->render_xhtml($renderer, $tocPosition);
        } // end of switch
        return false;
    }

    /**
     * render PLACEHOLDER, which will be replaced later
     * through action event handler handlePostProcess()
     */
    protected function render_xhtml(Doku_Renderer $renderer, $tocPosition) {
        global $ID;
        static $call_counter = [];  // holds number of ~~TOC_HERE~~ used in the page

        if ($call_counter[$ID]++ > 0) {
            return false;
        } elseif (isset($tocPosition) && ($tocPosition == -1)) {
            $renderer->doc .= self::TOC_HERE;
            return true;
        }
        return false;
    }

}
