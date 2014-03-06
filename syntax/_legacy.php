<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak legacy
 * provide backward compatibility for
 * TOC plugin revision 1 (2009-09-23) by Andriy Lesyuk
 * @see also http://projects.andriylesyuk.com/projects/toc
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

require_once(dirname(__FILE__).'/autotoc.php');

class syntax_plugin_toctweak_legacy extends syntax_plugin_toctweak_autotoc {

    protected $special_pattern = '~~TOC~~';
    protected $place_holder = '<!-- TOC -->';

    public function getType() { return 'substition'; }
    public function getPType(){ return 'normal'; }
    public function getSort() { return 30; }

}
