<?php
/**
 * DokuWiki plugin TOC Tweak; Action toctweak rendertoc
 * move toc position in the page with optional css class
 *
 * developed from TOC plugin revision 1 (2009-09-23) by Andriy Lesyuk
 * @see also http://projects.andriylesyuk.com/projects/toc
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class action_plugin_toctweak_rendertoc extends DokuWiki_Action_Plugin {

    protected $toptoclevel; // highest heading level which can appear in table of contents
    protected $maxtoclevel; // lowest heading level to include in table of contents
    protected $tocminheads; // minimum amount of headlines to show table of contents


    /**
     * Register event handlers
     */
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, '_setTocControl', array());
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handle_act_render', array());
        $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'BEFORE', $this, 'handlePostProcess', array());
    }

    /**
     * PARSER_CACHE_USE
     * Overwrites TOC-related config parameters
     */
    function _setTocControl(Doku_Event $event, $param) {  // _setTocControl
        global $conf;

        $cache =& $event->data;
        if (!isset($cache->page)) return;

        // preserve site global configuration parameters as class properties
        $keys = array('toptoclevel', 'maxtoclevel', 'tocminheads');
        foreach ($keys as $k) { 
            if ($this->$k == null) $this->$k = $conf[$k]; 
        }

        // retrieve metadata
        $topLv = p_get_metadata($cache->page, 'toc toptoclevel', METADATA_DONT_RENDER);
        $maxLv = p_get_metadata($cache->page, 'toc maxtoclevel', METADATA_DONT_RENDER);

        // modify $conf with metadata if available, otherwise restore to original value
        $conf['toptoclevel'] = $topLv ?: $this->toptoclevel;
        $conf['maxtoclevel'] = $maxLv ?: $this->maxtoclevel;

        // show TOC whenever possible if toc parameters have stored in metadata storage
        $conf['tocminheads'] = ($topLv || $maxLv) ? 1 : $this->tocminheads;
    }

    /**
     * TPL_ACT_RENDER
     * Make sure the other TOC is not printed
     */
    function handle_act_render(Doku_Event $event, $param) {
        global $INFO, $ACT;
        // TOC control should be changeable in only normal page
        if (in_array($ACT, array('show', 'preview')) == false) return;
        if (($INFO['meta']['toc']['position'] < 0)||($this->getConf('tocPosition') > 0)) {
                $INFO['prependTOC'] = false;
        }
    }

    /**
     * RENDERER_CONTENT_POSTPROCESS
     * render TOC according to $tocPosition
     * -1: PLACEHOLDER set by syntax component
     *  0: default. TOC will not moved (tocPostion config option)
     *  1: set PLACEHOLDER after the first heading (tocPosition config option)
     *  2: set PLACEHOLDER after the first level 1 heading (tocPosition config optipn)
     */
    function handlePostProcess(Doku_Event $event, $param) {
        global $INFO, $ID, $TOC, $ACT;
        // TOC control should be changeable in only normal page
        if (in_array($ACT, array('show', 'preview')) == false) return;

        // exclude sidebar, etc.
        if ($INFO['id'] != $ID) return;

        // exclude <dokuwiki>/inc/lang/<ISO>/preview.txt file.
        if ($ACT=='preview') {
            if (preg_match('#(<h[1-6]).*?>(.*?)</\1>#', $event->data[1], $matches)) {
                if ($matches[2] != hsc($INFO['meta']['title'])) return;
            }
        } 

        // TOC Position
        $tocPosition = $this->getConf('tocPosition');
        if ($ACT=='preview') {
            if (preg_match('#<!-- (?:TOC|INLINETOC) .*?-->#', $event->data[1])) {
                $tocPosition = -1;
            }
        } elseif (isset($INFO['meta']['toc']['position'])) {
                $tocPosition = $INFO['meta']['toc']['position'];
        }

        // set PLACEHOLDER according the tocPostion config setting
        switch ($tocPosition) {
            case 0:
                //$event->data[1] = '<!-- TOC -->'.$event->data[1];
                break;
            case 1:
                $event->data[1] = preg_replace('#</(h[1-6])>#', "</$1>\n".'<!-- TOC -->', $event->data[1], 1);
                break;
            case 2:
                $event->data[1] = preg_replace('#</h1>#', "</h1>\n".'<!-- TOC -->', $event->data[1], 1);
                break;
        }

        // replace PLACEHOLDER1s with tpl_toc() HTML output
        if (preg_match('#<!-- TOC .*?-->#', $event->data[1])) {
            $html = tpl_toc(true);
            $event->data[1] = preg_replace('#<!-- TOC .*?-->#', $html, $event->data[1]);
            // add class to TOC box
            if (isset($INFO['meta']['toc']['class'])) {
                $search =  '<div id="dw__toc"';
                $replace = $search.' class="'.$INFO['meta']['toc']['class'].'"';
                $event->data[1] = str_replace($search, $replace, $event->data[1]);
            }
        }

        // replace PLACEHOLDER2s with tpl_inlinetoc() HTML output
        if (preg_match('#<!-- INLINETOC .*?-->#', $event->data[1])) {
            $html = $this->tpl_inlinetoc(true);
            $event->data[1] = preg_replace('#<!-- INLINETOC .*?-->#', $html, $event->data[1]);
            // add class to TOC box
            if (isset($INFO['meta']['toc']['class'])) {
                $search =  '<div id="dw__inlinetoc"';
                $replace = $search.' class="'.$INFO['meta']['toc']['class'].'"';
                $event->data[1] = str_replace($search, $replace, $event->data[1]);
            }
        }
    }

    /**
     * Places the Inline TOC where the function is called
     */
    function tpl_inlinetoc($return = false) {
        global $lang, $TOC;
        if (is_array($TOC)) {
            $html = '<!-- INLINETOC START -->'.DOKU_LF;
            $html.= '<div id="dw__inlinetoc">'.DOKU_LF;
            $html.= '<h3>'.$lang['toc'].'</h3>';
            $html.= html_buildlist($TOC, 'inlinetoc', array($this, 'html_list_inlinetoc'));
            $html.= '</div>'.DOKU_LF;
            $html.= '<!-- INLINETOC END -->'.DOKU_LF;
        }
        if ($return) return $html;
        echo $html;
        return '';
    }

    /**
     * Callback for html_buildlist.
     * Builds list items with inlinetoc printable class 
     */
    function html_list_inlinetoc($item) {
        if (isset($item['hid'])) {
            $link = '#'.$item['hid'];
        } else {
            $link = $item['link'];
        }
        $html = '<span class="li"><a href="'.$link.'" class="inlinetoc">';
        $html.= hsc($item['title']).'</a></span>';
        return $html;
    }

}
