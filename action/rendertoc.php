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

    const TOC_HERE = '<!-- TOC_HERE -->';

    /**
     * Register event handlers
     */
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'handleParserCache');

        $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'BEFORE', $this, 'handlePostProcess');
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handleActRender');
        $controller->register_hook('TPL_TOC_RENDER', 'BEFORE', $this, 'handleTocRender');
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, 'handleContentDisplay');
    }


    protected function _setupTocConfig($active=true) {
        global $conf;
        if (!$this->getConf('tocAllHeads')) return;

        // Overwrite toc config parameters to catch up all headings in pages
        //  toptoclevel : highest heading level which can appear in table of contents
        //  maxtoclevel : lowest heading level to include in table of contents
        //  tocminheads : minimum amount of headlines to show table of contents
        // Note: setting tocminheads to 1 may cause trouble in preview.txt ?

        $conf['toptoclevel'] = 1;
        $conf['maxtoclevel'] = ($active) ? 5 : 0;
        $conf['tocminheads'] = $this->getConf('tocminheads'); 
    }

    /**
     * PARSER_CACHE_USE
     * Overwrite TOC config parameters to catch up all headings in pages
     */
    function handleParserCache(Doku_Event $event, $param) {
        $cache =& $event->data;

        // force set toc config parameters for pages (except locale XHTML files)
        // exception when $cache->page is blank, we assume it is some locale wiki
        // text and $conf['maxtoclevel'] = 0 to prepend adding placeholder in
        // handlePostProcess()

        if ($cache->page) {
            $active = true;
            $this->_setupTocConfig($active);
        } else {
            ($cache->mode == 'i') && $this->_setupTocConfig(false);
            return;
        }

        // manipulate cache validity (to get correct toc of other page)
        switch ($cache->mode) {
            case 'i':        // instruction cache
            case 'metadata': // metadata cache
                break;
            case 'xhtml':    // xhtml cache
                // request check with additional dependent files
                $depends = p_get_metadata($cache->page, 'relation toctweak');
                if (!$depends) break;
                $cache->depends['files'] = (!empty($cache->depends['files']))
                        ? array_merge($cache->depends['files'], $depends)
                        : $depends;
        } // end of switch
        return;
    }

    /**
     * RENDERER_CONTENT_POSTPROCESS
     * set placeholder (to be replaced with html of toc) according to tocPosition
     * -1: PLACEHOLDER set by syntax component
     *  0: default. TOC will not moved
     *  1: set PLACEHOLDER after the first level 1 heading
     *  2: set PLACEHOLDER after the first level 2 heading
     *  6: set PLACEHOLDER after the first heading
     *
     * Note: $event->data[1] dose not contain html of table of contents.
     */
    function handlePostProcess(Doku_Event $event, $param) {
        global $INFO, $ID, $ACT, $conf;

        // Workaround for locale wiki text that dose not need any TOC
        if ($conf['maxtoclevel'] == 0) {
            // once locale XHTML has rendered, therefore reset toc config parameters
            $this->_setupTocConfig();
            return;
        }

     // if (in_array($ACT, array('show', 'preview')) == false) return;

        $meta =& $INFO['meta']['toc'];

        // exclude sidebar, etc.
        if ($INFO['id'] != $ID) return;

        // TOC Position
        $tocPosition = @$meta['position'] ?: $this->getConf('tocPosition');
        if ($ACT=='preview') {
            if (strpos($event->data[1], self::TOC_HERE) !== false) {
                $tocPosition = -1;
            }
        }

        // set PLACEHOLDER according to tocPostion config setting
        switch ($tocPosition) {
            case -1: // locator has already set placeholder
            case 0:  // means no need to set placeholder, keep original position
            case 9:  // means do not show auto-toc except {{TOC}}
                return;
            case 6:
                $search  = '#</(h[1-6])>#';
                $replace = '</$1>'.self::TOC_HERE.DOKU_LF;
                break;
            default: // 1,2,3,4,5
                $search  = '#</(h'.$tocPosition.')>#';
                $replace = '</$1>'.self::TOC_HERE.DOKU_LF;
        } // end of switch
        $event->data[1] = preg_replace($search, $replace, $event->data[1], 1, $count);
    }

    /**
     * TPL_ACT_RENDER
     * hide auto-toc when it should be shown at different position,
     * The placeholder which is set during handlePostProcess() will be
     * replaced with html of toc in handleContentDisplay() stage.
     */
    function handleActRender(Doku_Event $event, $param) {
        global $INFO;
        $meta =& $INFO['meta']['toc'];
        $tocPosition = @$meta['position'] ?: $this->getConf('tocPosition');

        if (($tocPosition <> 0) or isset($meta['class'])) {
            $INFO['prependTOC'] = false;
        }
    }

    /**
     * TPL_TOC_RENDER
     * Pre-/postprocess the TOC array
     */
    function handleTocRender(Doku_Event $event, $param) {
        global $INFO, $conf;
        $toc =& $event->data; // = $TOC

        // retrieve toc config parameters from metadata
        $meta =& $INFO['meta']['toc'];
        $topLv = @$meta['toptoclevel'] ?: $this->getConf('toptoclevel');
        $maxLv = @$meta['maxtoclevel'] ?: $this->getConf('maxtoclevel');

        $items = array();
        foreach ($toc as $item) {
            // get headline level in real page
            $Lv = $item['level'] + $conf['toptoclevel'] -1;
            // get new toc level from header level
            $tocLv = $Lv - $topLv +1;

            if (($Lv < $topLv) || ($Lv > $maxLv)) {
                continue;
            }
            $item['level'] = $tocLv;
            $items[] = $item;
        }
        $event->data = $items;
    }

    /**
     * TPL_CONTENT_DISPLAY
     * Post process the XHTML output - Replace TOC PLACEHOLDER
     */
    function handleContentDisplay(Doku_Event $event, $param) {
        global $INFO, $TOC;

        if ($INFO['prependTOC'] == true) return; // nothing to do here

        // get html of built-in TOC that has be modified in handleTocRender()
        if (!count($TOC)) return;
        $toc_html = html_TOC($TOC); // use function in inc/html.php

        $meta =& $INFO['meta']['toc'];

        if (isset($meta['class'])) {
            $search =  '<div id="dw__toc"';
            $replace = $search.' class="'.hsc($meta['class']).'"';
            $toc_html = str_replace($search, $replace, $toc_html);
        }

        $tocPosition = @$meta['position'] ?: $this->getConf('tocPosition');

        // try to replace placeholder according to tocPostion
        if ($tocPosition == 9) {
            return;
        } elseif (strpos($event->data, self::TOC_HERE) !== false) {
            $event->data = str_replace(self::TOC_HERE, $toc_html, $event->data, $count);
        }
        // show toc original position if placeholder replacement failed
        if (($tocPosition == 0) or !$count) {
            $event->data = $toc_html.DOKU_LF.$event->data;
        }

        return;
    }

}
