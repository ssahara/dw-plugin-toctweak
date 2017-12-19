<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak inlinetoc
 * render toc inside the page content
 * 
 * provide compatibility for Andreone's inlinetoc plugin
 * @see also https://www.dokuwiki.org/plugin:inlinetoc
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

require_once(dirname(__FILE__).'/metatoc.php');

class syntax_plugin_toctweak_inlinetoc extends syntax_plugin_toctweak_metatoc {

    protected $pattern = array(
        5 => '{{INLINETOC\b.*?}}',  // DOKU_LEXER_SPECIAL
    );
    protected $tocStyle = array(  // default toc visual design
        'INLINETOC' => 'toc_inline',
    );
}
