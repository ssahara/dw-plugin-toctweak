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
        $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'BEFORE', $this, 'handlePostProcess');
    }

    protected function _setupTocConfig() {
        global $conf;

        // Overwrite toc config parameters to catch up all headings in pages
        //  toptoclevel : highest heading level which can appear in table of contents
        //  maxtoclevel : lowest heading level to include in table of contents
        //  tocminheads : minimum amount of headlines to show table of contents
        // Note: setting tocminheads to 1 may cause trouble in preview.txt ?

        $conf['toptoclevel'] = 1;
        $conf['maxtoclevel'] = 5;
        $conf['tocminheads'] = $this->getConf('tocminheads'); 
    }

    /**
     * PARSER_CACHE_USE
     * Overwrite TOC config parameters to catch up all headings in pages
     */
    function handleParserCache(Doku_Event $event, $param) {
        // force set toc config parameters
        if ($this->getConf('tocAllHeads')) {
            $this->_setupTocConfig();
        }

        // manipulate cache validity
        $cache =& $event->data;
        if (!isset($cache->page)) return;
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
        global $INFO, $ACT;
        // TOC control should be changeable in only normal page
        if (in_array($ACT, array('show', 'preview')) == false) return;
        if (($INFO['meta']['toc']['position'] < 0)||($this->getConf('tocPosition') > 0)) {
                $INFO['prependTOC'] = false;
             // $INFO['prependTOC'] = true;  // DEBUG anyway show original TOC
        }
    }

    /**
     * RENDERER_CONTENT_POSTPROCESS
     * render TOC according to $tocPosition
     * -1: PLACEHOLDER set by syntax component
     *  0: default. TOC will not moved (tocPostion config option)
     *  1: set PLACEHOLDER after the first level 1 heading (tocPosition config optipn)
     *  6: set PLACEHOLDER after the first heading (tocPosition config option)
     */
    function handlePostProcess(Doku_Event $event, $param) {
        global $INFO, $ID, $ACT, $TOC;

        if (in_array($ACT, array('show', 'preview')) == false) return;

        $meta =& $INFO['meta']['toc'];

        // exclude sidebar, etc.
        if ($INFO['id'] != $ID) return;

        // TOC Position
        $tocPosition = @$meta['position'] ?: $this->getConf('tocPosition');
        if ($ACT=='preview') {
            if (preg_match('#<!-- TOC .*?-->#', $event->data[1])) {
                $tocPosition = -1;
            }
        }

        // set PLACEHOLDER according to tocPostion config setting
        if ($tocPosition >= 0) {
            $topLv = @$meta['toptoclevel'] ?: $this->getConf('toptoclevel');
            $maxLv = @$meta['maxtoclevel'] ?: $this->getConf('maxtoclevel');
            $tocClass = @$meta['class'] ?: '';
            $placeHolder = '<!-- TOC '.$topLv.'-'.$maxLv.' '.$tocClass.' -->';
            switch ($tocPosition) {
                case 0:
                    $event->data[1] = $placeHolder.DOKU_LF.$event->data[1];
                    break;
                case 1:
                    $event->data[1] = preg_replace('#</h1>#', "</h1>\n".$placeHolder, $event->data[1], 1);
                    break;
                case 6:
                    $event->data[1] = preg_replace('#</(h[1-6])>#', "</$1>\n".$placeHolder, $event->data[1], 1);
                    break;
            }
        }

        // replace PLACEHOLDERs
        $placeHolder = '#<!-- TOC (\d+)-(\d+)(?: (.*?))? -->#'; // regex

        if (preg_match_all($placeHolder, $event->data[1], $tokens, PREG_SET_ORDER)) {

            foreach ($tokens as $token) {
                $html = $this->html_toc($token[1], $token[2]);
                if (!empty($token[3])) {
                            $search =  '<div id="dw__toc"';
                            $replace = $search.' class="'.trim($token[4]).'"';
                            $html = str_replace($search, $replace, $html);
                }
                $event->data[1] = str_replace($token[0], $html, $event->data[1]);
            }
        }
    }

    /**
     * Return html of customized TOC
     */
    private function html_toc($topLv, $maxLv) {
        global $TOC;
        if (!count($TOC)) return '';

        $items = $this->trim_toc($TOC, $topLv, $maxLv);
        $html = html_TOC($items); // use function in inc/html.php
        return $html;
    }

    private function trim_toc(array $toc, $topLv, $maxLv) {
        global $conf;

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
        return $items;
    }

}
