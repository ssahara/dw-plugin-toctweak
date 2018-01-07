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
     // $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, '_initTocConfig');
        $controller->register_hook('ACTION_ACT_PREPROCESS','BEFORE', $this, 'handleActPreprocess');
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'handleParserCache');

        $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'BEFORE', $this, 'handlePostProcess');
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handleActRender');
     // $controller->register_hook('TPL_TOC_RENDER', 'BEFORE', $this, 'handleTocRender');
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, 'handleContentDisplay');
    }


    /*
     * Overwrite toc config parameters to catch up all headings in pages
     *  toptoclevel : highest headline level which can appear in table of contents
     *  maxtoclevel : lowest headline level to include in table of contents
     *  tocminheads : minimum amount of headlines to show table of contents
     */
    function _setTocConfig() {
        global $conf;
        $active = $this->getConf('tocAllHeads');
        if ($conf['toptoclevel'] != 1) {
            $conf['toptoclevel'] = $active ? 1 : $this->getConf('toptoclevel');
        }
        if ($conf['maxtoclevel'] != 5) {
            $conf['maxtoclevel'] = $active ? 5 : $this->getConf('maxtoclevel');
        }
        if ($conf['tocminheads'] != $this->getConf('tocminheads')) {
            $conf['tocminheads'] = $this->getConf('tocminheads');
        }
    }

    /**
     * DOKUWIKI_STARTED
     */
    function _initTocConfig(Doku_Event $event, $param) {
        $this->_setTocConfig();
    }

    /**
     * ACTION_ACT_PREPROCESS
     * catch action mode before dispacher begins to process the $ACT variable
     */
    function handleActPreprocess(Doku_Event $event, $param) {
        global $conf, $ACT, $ID;
        if ($event->data == 'admin') {
            // admin plugins such as the Config Manager may have own TOC
            // that might be depend on global toc parameters?
            $conf['toptoclevel'] = 1;
            $conf['maxtoclevel'] = 3;
            $conf['tocminheads'] = 3;
        } else {
            $this->_setTocConfig();
        }
        return;
    }

    /**
     * PARSER_CACHE_USE
     * manipulate cache validity (to get correct toc of other page)
     */
    function handleParserCache(Doku_Event $event, $param) {
        global $conf;

        $this->_setTocConfig(); //!! ensure correct toc parameters have set

        $cache =& $event->data;

        if (!$cache->page) return;

        switch ($cache->mode) {
            case 'i':        // instruction cache
            case 'metadata': // metadata cache
                break;
            case 'xhtml':    // xhtml cache
                // request check with additional dependent files
                $depends = p_get_metadata($cache->page, 'relation toctweak');
                if (!$depends) break;
                $cache->depends['files'] = ($cache->depends['files'])
                        ? array_merge($cache->depends['files'], $depends)
                        : $depends;
        } // end of switch
        return;
    }

    /**
     * RENDERER_CONTENT_POSTPROCESS
     * replace placeholder set by autotoc syntax component with html_toc
     * this must happen only when tocPosition is -1
     * Note: Do not add/insert html of auto-toc in this event handler
     * to avoid locale text (such as preview.txt) has unwanted toc box.
     */
    function handlePostProcess(Doku_Event $event, $param) {
        global $ACT, $INFO;
        $meta =& $INFO['meta']['toc'];

        // Action mode check
        if (!in_array($ACT, ['show','preview'])) {
            return;
        }

        // TOC Position check
        $tocPosition = @$meta['position'] ?: $this->getConf('tocPosition');
        if ($ACT == 'preview') {
            if (strpos($event->data[1], self::TOC_HERE) !== false) {
                $tocPosition = -1;
            }
        }
        if ($tocPosition != -1) {
            return;
        }

        // retrieve toc config parameters from metadata
        $topLv = @$meta['toptoclevel'] ?: $this->getConf('toptoclevel');
        $maxLv = @$meta['maxtoclevel'] ?: $this->getConf('maxtoclevel');
        $headline = '';
        $toc = @$INFO['meta']['description']['tableofcontents'] ?: array();

        // load helper object
        isset($tocTweak) || $tocTweak = $this->loadHelper($this->getPluginName());

        // prepare html of table of content
        $html_toc = $tocTweak->html_toc($toc, $topLv, $maxLv, $headline);

        // replace PLACEHOLDER with html_toc
        $event->data[1] = str_replace(self::TOC_HERE, $html_toc, $event->data[1], $count);
        return;
    }

    /**
     * TPL_ACT_RENDER
     * hide auto-toc that is to be rendered in handleContentDisplay()
     */
    function handleActRender(Doku_Event $event, $param) {
        global $ACT, $INFO;

        // Action mode check
        if (in_array($ACT, ['show','preview'])) {
            $INFO['prependTOC'] = false;
        }
        return;
    }

    /**
     * TPL_TOC_RENDER
     * Pre-/postprocess the TOC array
     */
    function handleTocRender(Doku_Event $event, $param) {
        global $ACT, $INFO;
        $meta =& $INFO['meta']['toc'];

        // Action mode check
        if (!in_array($ACT, ['show','preview'])) {
            return;
        }

        // retrieve toc config parameters from metadata
        $topLv = @$meta['toptoclevel'] ?: $this->getConf('toptoclevel');
        $maxLv = @$meta['maxtoclevel'] ?: $this->getConf('maxtoclevel');
        $headline = '';
        $toc = $event->data ?: array(); // data is reference to global $TOC

        // load helper object
        isset($tocTweak) || $tocTweak = $this->loadHelper($this->getPluginName());

        $event->data = $tocTweak->_toc($toc, $topLv, $maxLv, $headline);
    }

    /**
     * TPL_CONTENT_DISPLAY
     * insert XHTML of auto-toc at tocPosition where
     *  0: top of the content (default)
     *  1: after the first level 1 heading
     *  2: after the first level 2 heading
     *  6: after the first heading
     */
    function handleContentDisplay(Doku_Event $event, $param) {
        global $ACT, $INFO;
        $meta =& $INFO['meta']['toc'];

        // Action mode check
        if (!in_array($ACT, ['show','preview'])) {
            return;
        }

        // TOC Position check
        $tocPosition = @$meta['position'] ?: $this->getConf('tocPosition');
        if (!in_array($tocPosition, [0,1,2,6])) {
            return;
        }

        // retrieve toc config parameters from metadata
        $topLv = @$meta['toptoclevel'] ?: $this->getConf('toptoclevel');
        $maxLv = @$meta['maxtoclevel'] ?: $this->getConf('maxtoclevel');
        $headline = '';
        $toc = @$INFO['meta']['description']['tableofcontents'] ?: array();

        // load helper object
        isset($tocTweak) || $tocTweak = $this->loadHelper($this->getPluginName());

        // prepare html of table of content
        $html_toc = $tocTweak->html_toc($toc, $topLv, $maxLv, $headline);

        // get html content of current page from event data, exclude editor UI
        if ($ACT == 'preview') {
            $search = '<div class="preview"><div class="pad">';
            $offset = strpos($event->data, $search) + strlen($search);
            $content = substr($event->data, $offset);
        } else {
            $content = $event->data;
        }

        // Step 1: set PLACEHOLDER based on tocPostion config setting
        if ($tocPosition == 0) {
            $content = self::TOC_HERE.$content;
            $count = 1;
        } else {
            $search  = '#</(h'.(($tocPosition == 6) ? '[1-6]' : $tocPosition).')>#';
            $replace = '</$1>'.self::TOC_HERE;
            $content= preg_replace($search, $replace, $content, 1, $count);
            if (!$count) {
                // show toc original position if placeholder replacement failed
                $content = self::TOC_HERE.$content;
                $count = 1;
            }
            unset($search, $replace);
        }

        // Step 2: replace PLACEHOLDER with html_toc
        if ($count > 0) {
            // try to replace placeholder according to tocPostion
            $content = str_replace(self::TOC_HERE, $html_toc, $content, $count);
        }

        // reflect content to event data
        if ($ACT == 'preview') {
            $event->data = substr($event->data, 0, $offset).$content;
        } else {
            $event->data = $content;
        }
        return;
    }

}
