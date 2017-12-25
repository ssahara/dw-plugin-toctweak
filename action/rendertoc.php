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

    const TOC_HERE = '<!-- TOC_HERE -->'.DOKU_LF;

    /**
     * Register event handlers
     */
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'handleParserCache');

        $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'BEFORE', $this, 'handlePostProcess');
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handleActRender');
        $controller->register_hook('TPL_TOC_RENDER', 'BEFORE', $this, 'handleTocRender');
    }


    /**
     * Overwrite toc config parameters to catch up all headings in pages
     *  toptoclevel : highest headline level which can appear in table of contents
     *  maxtoclevel : lowest headline level to include in table of contents
     *  tocminheads : minimum amount of headlines to show table of contents
     *
     * Note: setting tocminheads to 1 may cause trouble in preview.txt ?
     */
    protected function _setupTocConfig($active=true) {
        global $ACT, $conf;

        if (!$this->getConf('tocAllHeads')) return;

        if (in_array($ACT, ['admin'])) {
            // admin plugins such as the Congig Manager may have own TOC
            $conf['toptoclevel'] = 1;
            $conf['maxtoclevel'] = 3;
            $conf['tocminheads'] = 3;
        } else {
            // try to disable TOC generation for locale wiki files
            // e.g. for preview.txt
            $conf['toptoclevel'] = 1;
            $conf['maxtoclevel'] = ($active) ? 5 : 0;
            $conf['tocminheads'] = $this->getConf('tocminheads');
        }
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
            $this->_setupTocConfig();
        } else {
            // assume as locale wiki files
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
        global $INFO, $TOC, $conf;

        // Workaround for locale wiki text that dose not need any TOC
        if ($conf['maxtoclevel'] == 0) {
            // once locale XHTML has rendered, then reset toc config parameters
            $this->_setupTocConfig();
            return;
        }

        $meta =& $INFO['meta']['toc'];

        // TOC Position
        $tocPosition = @$meta['position'] ?: $this->getConf('tocPosition');
        if ($ACT == 'preview') {
            if (strpos($event->data[1], self::TOC_HERE) !== false) {
                $tocPosition = -1;
            }
        }

        // Stage 1: prepare html of table of content
        // Note: $TOC must be modified in handleTocRender() if necessary
        $toc_html = html_TOC($TOC); // use function in inc/html.php
        if ($toc_html && isset($meta['class'])) {
            $search =  '<div id="dw__toc"';
            $replace = $search.' class="'.hsc($meta['class']).'"';
            $toc_html = str_replace($search, $replace, $toc_html);
            unset($search, $replace);
        }

        // Stage 2: set PLACEHOLDER according to tocPostion config setting
        switch ($tocPosition) {
            case -1: // already set placeholder by syntax/autotoc.php
                $count = 1;
                break;
            case 0:  // means no need to set placeholder, keep original position
                if (isset($meta['class'])) {
                    $event->data[1] = self::TOC_HERE.$event->data[1];
                    $count = 1;
                }
                break;
            case 9:  // means do not show auto-toc except {{TOC_HERE}}
                break;
            default: // 1,2,3,4,5 or 6
                $search  = '#</(h'.(($tocPosition == 6) ? '[1-6]' : $tocPosition).')>#';
                $replace = '</$1>'.self::TOC_HERE;
                $event->data[1] = preg_replace($search, $replace, $event->data[1], 1, $count);
                if (!$count) {
                    // show toc original position if placeholder replacement failed
                    $event->data[1] = self::TOC_HERE.$event->data[1];
                    $count = 1;
                }
                unset($search, $replace);
        } // end of switch

        // Stage 3: replace PLACEHOLDER
        if ($count > 0) {
            // try to replace placeholder according to tocPostion
            $event->data[1] = str_replace(self::TOC_HERE, $toc_html, $event->data[1], $count);
        }
        return;
    }

    /**
     * TPL_ACT_RENDER
     * hide auto-toc when it should be shown at different position
     */
    function handleActRender(Doku_Event $event, $param) {
        global $ACT, $INFO;

        // admin plugins such as the Congig Manager may have own TOC
        if (in_array($ACT, ['admin'])) {
            return;
        }

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
        global $ACT, $INFO, $conf;

        // admin plugins such as the Congig Manager may have own TOC
        if (in_array($ACT, ['admin'])) {
            return;
        }

        // retrieve toc config parameters from metadata
        $meta =& $INFO['meta']['toc'];
        $topLv = @$meta['toptoclevel'] ?: $this->getConf('toptoclevel');
        $maxLv = @$meta['maxtoclevel'] ?: $this->getConf('maxtoclevel');

        $toc =& $event->data; // reference to global $TOC
        $items = array();
        foreach ($toc as $item) {
            // get headline level in real page
            $Lv = $item['level'] + $conf['toptoclevel'] -1;
            // get new toc level from headline level
            $tocLv = $Lv - $topLv +1;

            if (($Lv < $topLv) || ($Lv > $maxLv)) {
                continue;
            }
            $item['level'] = $tocLv;
            $items[] = $item;
        }
        $event->data = $items;
    }

}
