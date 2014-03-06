<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak control
 * set top and max level of headings of the page with optional css class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

require_once(dirname(__FILE__).'/movetoc.php');

class syntax_plugin_toc_control extends syntax_plugin_toc_movetoc {

    protected $special_pattern = '~~TOC:?.*?~~';

}
