<?php
/**
 * TocTweak plugin for DokuWiki; Syntax autotochere
 * render toc placeholder to show built-in toc box in the page
 * set top and max level of headlines to be found in table of contents
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

require_once(dirname(__FILE__).'/autotoc.php');

class syntax_plugin_toctweak_autotochere extends syntax_plugin_toctweak_autotoc {

    protected $pattern = array(
        5 => '~~TOC_HERE(?:_CLOSED)?\b.*?~~',
    );
}
