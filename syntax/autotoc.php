<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak autotoc
 * set top and max level of headings of the page with optional css class
 * syntax of this component dose not relocate TOC,
 * however supports PLACEHOLDER output for other inherited components
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_toctweak_autotoc extends DokuWiki_Syntax_Plugin {

    protected $special_pattern = '~~TOC:?.*?~~';
    protected $place_holder = '<!-- TOC -->'; // dummy for this compo

    public function getType() { return 'substition'; }
    public function getPType(){ return 'block'; }
    public function getSort() { return 30; }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->special_pattern,$mode,
            implode('_', array('plugin',$this->getPluginName(),$this->getPluginComponent(),))
        );
    }

    public function handle($match, $state, $pos, Doku_Handler &$handler) {
        return array($state, $match);
    }

    public function render($mode, Doku_Renderer &$renderer, $indata) {
        if (empty($indata)) return false;
        list($state, $data) = $indata;

        // get where and how the TOC should be located in the page
        // -1: PLACEHOLDER set by syntax component
        //  0: default. TOC will not moved (tocPostion config option)
        //  1: set PLACEHOLDER after the first heading (tocPosition config option)
        //  2: set PLACEHOLDER after the first level 1 heading (tocPosition config optipn)
        $tocPosition = (substr($data, 0, 2) == '{{') ? -1 : 0;

        // strip and split markup
        $matches = preg_split('/[:\s]+/', substr($data, 2, -2), 2);
        $match = $matches[1];

        // get TOC generation parameter, to be vefified in action component
        if (preg_match('/(\d+)?\s*-?\s*(\d+)?/', $match, $matches)) {
            $topLv = $matches[1];
            $maxLv = $matches[2];
            $match = str_replace($matches[0], '', $match);
        }

        // get class name for TOC box, ensure excluded any malcious character
        $tocClass = (preg_match('/[^ A-Za-z0-9_-]/', $match)) ? '' : trim($match);

        if ($mode == 'xhtml') {
            // Add PLACEHOLDER to cached page (will be replaced by action component)
            if ($tocPosition < 0) $renderer->doc .= $this->place_holder;

        } elseif ($mode == 'metadata') {
            $renderer->meta['toc']['position'] = $tocPosition;
            $renderer->meta['toc']['toptoclevel'] = $topLv;
            $renderer->meta['toc']['maxtoclevel'] = $maxLv;
            $renderer->meta['toc']['class'] = $tocClass;

        } else {
            return false;
        }
        return true;
    }

}
