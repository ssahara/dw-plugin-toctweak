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

    /**
     * Register event handlers
     */
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'handleParserCache');

        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handleActRender');
        $controller->register_hook('TPL_TOC_RENDER', 'BEFORE', $this, 'handleTocRender');
        $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'BEFORE', $this, 'handlePostProcess');
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
     * TPL_ACT_RENDER
     * Make sure the built-in TOC is not printed
     */
    function handleActRender(Doku_Event $event, $param) {
        global $INFO;
        if ($this->getConf('tocPosition') == 9) {
            $INFO['prependTOC'] = false; // disable built-in auto-toc
        }
    }

    /**
     * RENDERER_CONTENT_POSTPROCESS
     * set placeholder for TOC html "<!-- TOC -->" according to $tocPosition
     * -1: PLACEHOLDER set by syntax component
     *  0: default. TOC will not moved (tocPostion config option)
     *  1: set PLACEHOLDER after the first level 1 heading (tocPosition config optipn)
     *  6: set PLACEHOLDER after the first heading (tocPosition config option)
     *
     * Note: $event->data[1] dose not contain html of table of contents.
     */
    function handlePostProcess(Doku_Event $event, $param) {
        global $INFO, $ID, $ACT;

        // Workaround for locale wiki text that dose not need any TOC
        if ($conf['maxtoclevel'] == 0) {
            // once locale XHTML has rendered, therefore reset toc config parameters
            $this->_setupTocConfig();
            return;
        }

        if (in_array($ACT, array('show', 'preview')) == false) return;

        $meta =& $INFO['meta']['toc'];

        // exclude sidebar, etc.
        if ($INFO['id'] != $ID) return;

        // TOC Position
        $tocPosition = @$meta['position'] ?: $this->getConf('tocPosition');
        if ($ACT=='preview') {
            if (strpos($event->data[1], '<!-- TOC -->') !== false) {
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
                $replace = '</$1>'.'<!-- TOC -->'.DOKU_LF;
                break;
            default: // 1,2,3,4,5
                $search  = '#</(h'.$tocPosition.')>#';
                $replace = '</$1>'.'<!-- TOC -->'.DOKU_LF;
        } // end of switch
        $event->data[1] = preg_replace($search, $replace, $event->data[1], 1, $count);
    }

    /**
     * TPL_TOC_RENDER
     * Pre-/postprocess the TOC array
     */
    function handleTocRender(Doku_Event $event, $param) {
        global $INFO, $conf;
        $toc =& $event->data;

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
        global $INFO;

        if ($INFO['prependTOC'] == false) return;

        $meta =& $INFO['meta']['toc'];

        // find html of TOC and keep it as $matches[0]
        $search = '/<!-- TOC START -->.*?<!-- TOC END -->/ms';
        if (preg_match($search, $event->data, $matches) === false) {
            // $event->data dose not contain html of table of contents.
            return;
        } else {
            if (isset($meta['class'])) {
                $search =  '<div id="dw__toc"';
                $replace = $search.' class="'.hsc(trim($meta['class'])).'"';
                $toc_html = str_replace($search, $replace, $matches[0]);
            } else {
                $toc_html = $matches[0];
            }
        }

        // replace PLACEHOLDER according to tocPostion config setting
        $tocPosition = @$meta['position'] ?: $this->getConf('tocPosition');
        switch ($tocPosition) {
            case 0:
                // update html of built-in toc with modified toc
                $event->data = str_replace($matches[0], $toc_html, $event->data);
                return;
            case 9:
                // remove html of built-in toc
                $event->data = str_replace($matches[0], '', $event->data);
                return;
            default:
                if (strpos($event->data, '<!-- TOC -->') !== false) {
                    // remove html of built-in toc and replace placeholder with modified toc
                    $event->data = str_replace($matches[0], '', $event->data);
                    $event->data = str_replace('<!-- TOC -->', $toc_html, $event->data);
                } else {
                    // update html of built-in toc with class attribute
                    $event->data = str_replace($matches[0], $toc_html, $event->data);
                }
        } // end of switch
    }

}
