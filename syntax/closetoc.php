<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak closetoc
 * set toggle state initially closed (by script.js)
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_toc_closetoc extends DokuWiki_Syntax_Plugin {

    protected $special_pattern = '~~CLOSETOC~~';

    public function getType() { return 'substition'; }
    public function getPType(){ return 'block'; }
    public function getSort() { return 990; }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->special_pattern,$mode,
            implode('_', array('plugin',$this->getPluginName(),$this->getPluginComponent(),))
        );
    }

    public function handle($match, $state, $pos, &$handler) {
        return array($state, $match);
    }

    public function render($mode, &$renderer, $data) {
        if ($mode == 'metadata') {
             $renderer->meta['toc']['initial_state'] = -1;
        }
        return true;
    }

}

