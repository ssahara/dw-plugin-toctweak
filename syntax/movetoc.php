<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak movetoc
 * move toc position in the page with optional css class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

require_once(dirname(__FILE__).'/autotoc.php');

class syntax_plugin_toctweak_movetoc extends syntax_plugin_toctweak_autotoc {

    protected $special_pattern = '{{TOC:?.*?}}';
    protected $place_holder = '<!-- TOC -->';

}
