<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak locator
 * locate toc box with optional css class in the page
 * set top and max level of headings of the page with optional css class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class syntax_plugin_toctweak_locator extends DokuWiki_Syntax_Plugin {

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

        // load helper object
        $helper = $helper ?: $this->loadHelper($this->getPluginName());

        // strip markup
        $param = substr($match, strpos($this->pattern[5],':')+1, -2);

        list($topLv, $maxLv, $tocClass) = $helper->parse($param);

        $data = array($ID, $topLv, $maxLv, $tocClass);
        return $data;
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
        //  1: set PLACEHOLDER after the first level 1 heading (tocPosition config optipn)
        //  6: set PLACEHOLDER after the first heading (tocPosition config option)
        $tocPosition = -1;

        switch ($format) {
            case 'xhtml':
                // render PLACEHOLDER, which will be replaced by action component
                $placeHolder = '<!-- '.strstr(substr($this->pattern[5],2),':',1)
                              .' '.$topLv.'-'.$maxLv.' '.$tocClass.' -->';
                $renderer->doc .= $placeHolder . DOKU_LF;
             // error_log('..movetoc render: '.$placeHolder);
                return true;

            case 'metadata':
                $renderer->meta['toc']['position'] = $tocPosition;
                return true;
        }
        return false;
    }

}
