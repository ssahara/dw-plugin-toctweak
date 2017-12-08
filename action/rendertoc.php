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
/* ----------------------------------- */
    protected $toptoclevel; // highest heading level which can appear in table of contents
    protected $maxtoclevel; // lowest heading level to include in table of contents
    protected $tocminheads; // minimum amount of headlines to show table of contents

/* ----------------------------------- */

    /**
     * Register event handlers
     */
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, '_setVolatileConfig');
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, '_setTocControl', array());
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handle_act_render', array());
        $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'BEFORE', $this, 'handlePostProcess', array());
    }

    protected function _setupVolatileConfig() {
        global $conf;

        // preserve site global configuration parameters
        // highest heading level which can appear in table of contents
        // lowest heading level to include in table of contents
        // minimum amount of headlines to show table of contents
        if (!isset($conf['plugin']['toctweak']['_toptoclevel']) && is_numeric($conf['toptoclevel'])) {
            $conf['plugin']['toctweak']['_toptoclevel'] = $conf['toptoclevel'];
        } else {
            $conf['plugin']['toctweak']['_toptoclevel'] = 1;
        }
error_log('_setup _toc='.$conf['plugin']['toctweak']['_toptoclevel']);
        if (!isset($conf['plugin']['toctweak']['_maxtoclevel']) && is_numeric($conf['maxtoclevel'])) {
            $conf['plugin']['toctweak']['_maxtoclevel'] = $conf['maxtoclevel'];
        }
        if (!isset($conf['plugin']['toctweak']['_tocminheads']) && is_numeric($conf['tocminheads'])) {
            $conf['plugin']['toctweak']['_tocminheads'] = $conf['tocminheads'];
        }
    }



    /**
     * DOKUWIKI_STARTED
     */
    function _setVolatileConfig(Doku_Event $event, $param) {
        global $conf, $ID;

        // preserve site global configuration parameters
        $this->_setupVolatileConfig();

        $keys = array('_toptoclevel', '_maxtoclevel', '_tocminheads');
        foreach ($keys as $k) { 
            error_log('TOCTWEAK '.$ID.' '.$k.'='.$conf['plugin']['toctweak'][$k]);
        }

    }

    /**
     * PARSER_CACHE_USE
     * Overwrites TOC-related config parameters
     * clear volatile config settings that have set in syntax component
     */
    function _setTocControl(Doku_Event $event, $param) {  // _setTocControl
        global $conf;

        $cache =& $event->data;
        if (!isset($cache->page)) return;

        if (!is_array($conf['plugin']['toctweak'])) {
            $this->_setupVolatileConfig();
        }

        //DEBUG 
        $note = 'toctweak PARSER_CACHE_USE: '.$cache->page.' ';
        if (isset($conf['plugin']['toctweak']['_toptoclevel'])) {
            $note.= '_top='.$conf['plugin']['toctweak']['_toptoclevel'];
        } else {
            $note.= '_top=NOT AVAILABLE!';
            error_log('  '.var_export($conf['plugin']['toctweak'],1));
        }
        error_log($note);


/* ----------------------------------------------------------- */
        // preserve site global configuration parameters as class properties
        $keys = array('toptoclevel', 'maxtoclevel', 'tocminheads');
        foreach ($keys as $k) { 
            if ($this->$k == null) $this->$k = $conf[$k]; 
        }
/* ----------------------------------------------------------- */

        // restore site global configuration parameters
        //error_log('rendertoc top:'.$conf['plugin']['toctweak']['_toptoclevel'].' '.$conf['toptoclevel']);
        //error_log('rendertoc max:'.$conf['plugin']['toctweak']['_maxtoclevel'].' '.$conf['maxtoclevel']);

        $conf['toptoclevel'] = $conf['plugin']['toctweak']['_toptoclevel'];
        $conf['maxtoclevel'] = $conf['plugin']['toctweak']['_maxtoclevel'];
        $conf['tocminheads'] = 1;

        //error_log('rendertoc top:'.$conf['plugin']['toctweak']['_toptoclevel'].' '.$conf['toptoclevel']);
        //error_log('rendertoc max:'.$conf['plugin']['toctweak']['_maxtoclevel'].' '.$conf['maxtoclevel']);

        //return;


        // retrieve metadata
        $topLv = p_get_metadata($cache->page, 'toc toptoclevel', METADATA_DONT_RENDER);
        $maxLv = p_get_metadata($cache->page, 'toc maxtoclevel', METADATA_DONT_RENDER);
        //return;

        // modify $conf with metadata if available, otherwise restore to original value
        $conf['toptoclevel'] = $topLv ?: $conf['plugin']['toctweak']['_toptoclevel'];
        $conf['maxtoclevel'] = $maxLv ?: $conf['plugin']['toctweak']['_maxtoclevel'];

        error_log('rendertoc '.$cache->mode.' top1:'.$conf['plugin']['toctweak']['_toptoclevel'].' '.$conf['toptoclevel']);
        error_log('rendertoc '.$cache->mode.' max1:'.$conf['plugin']['toctweak']['_maxtoclevel'].' '.$conf['maxtoclevel']);

        //$conf['toptoclevel'] = $topLv ?: $this->getConf('_toptoclevel');
        //$conf['maxtoclevel'] = $maxLv ?: $this->getConf('_maxtoclevel');
        //return;
// -------------------------------- */ 
        $conf['toptoclevel'] = $topLv ?: $this->toptoclevel;
        $conf['maxtoclevel'] = $maxLv ?: $this->maxtoclevel;
// -------------------------------- */
        error_log('rendertoc top:'.$conf['plugin']['toctweak']['_toptoclevel'].' '.$conf['toptoclevel']);
        error_log('rendertoc max:'.$conf['plugin']['toctweak']['_maxtoclevel'].' '.$conf['maxtoclevel']);

        // show TOC whenever possible if toc parameters have stored in metadata storage
        $conf['tocminheads'] = ($topLv || $maxLv) ? 1 : $conf['plugin']['toctweak']['_tocminheads'];
// -------------------------------- */
        $conf['tocminheads'] = ($topLv || $maxLv) ? 1 : $this->tocminheads;
// -------------------------------- */
        $conf['tocminheads'] = 1;
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
        global $conf, $INFO, $ID, $TOC, $ACT;
        // TOC control should be changeable in only normal page
        if (in_array($ACT, array('show', 'preview')) == false) return;

    error_log('rendertoc post 1: '.
        $this->getConf('_toptoclevel').' '.
        $this->getConf('_maxtoclevel').' '
    );


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

        // replace PLACEHOLDERs
        $placeHolder = '#<!-- (TOC|INLINETOC)(?: (\d))?(?: (\d))?(?: (.*?))? -->#'; // regex
        if (preg_match_all($placeHolder, $event->data[1], $tokens, PREG_SET_ORDER)) {

            foreach ($tokens as $token) {

                //if (!isset($token[2])) $token[2] = $INFO['meta']['toc']['toptoclevel'] ?: $this->toptoclevel;
                //if (!isset($token[3])) $token[3] = $INFO['meta']['toc']['maxtoclevel'] ?: $this->maxtoclevel;
                if (!isset($token[2])) $token[2] = $INFO['meta']['toc']['toptoclevel'] ?: $this->getConf('_toptoclevel');
                if (!isset($token[3])) $token[3] = $INFO['meta']['toc']['maxtoclevel'] ?: $this->getConf('_maxtoclevel');
                if (!isset($token[4])) $token[4] = $INFO['meta']['toc']['class'];

                switch ($token[1]) {
                    case 'TOC':
                        $html = $this->html_toc($token[2], $token[3]);
                        if (!empty($token[4])) {
                            $search =  '<div id="dw__toc"';
                            $replace = $search.' class="'.trim($token[4]).'"';
                            $html = str_replace($search, $replace, $html);
                        }
                        break;
                    case 'INLINETOC':
                        $html = $this->html_inlinetoc($token[2], $token[3]);
                        if (!empty($token[4])) {
                            $search =  '<div id="dw__inlinetoc"';
                            $replace = $search.' class="'.trim($token[4]).'"';
                            $html = str_replace($search, $replace, $html);
                        }
                        break;
                }
                $event->data[1] = str_replace($token[0], $html, $event->data[1]);
            }
        }
    }

    /**
     * Return html of customized TOC
     */
    private function html_toc($topLv, $maxLv) {
        global $conf, $TOC;
        if (!count($TOC)) return '';
        $items = $this->trim_toc($TOC, $topLv, $maxLv);
        $html = html_TOC($items); // use function in inc/html.php
        return $html;
    }

    private function trim_toc(array $toc, $topLv, $maxLv) {
        global $conf;

        $items = array();
        foreach ($toc as $item) {
            // get header level from original toc level
            $headerLv = $item['level'] + $conf['toptoclevel'] -1;
            // get new toc level from header level
            $tocLv = $headerLv - $topLv +1;

            if (($headerLv < $topLv) || ($headerLv > $maxLv)) {
                continue;
            }
            $item['level'] = $tocLv;
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Return html of customized INLINETOC
     */
    private function html_inlinetoc($topLv, $maxLv) {
        global $conf, $TOC;

        if (!count($TOC)) return '';

        $items = $this->trim_toc($TOC, $topLv, $maxLv);

        if (!empty($items)) {
            $html = '<!-- INLINETOC START -->'.DOKU_LF;
            $html.= '<div id="dw__inlinetoc">'.DOKU_LF;
            $html.= '<h3>'.$lang['toc'].'</h3>';
            $html.= html_buildlist($items, 'inlinetoc', array($this, 'html_list_inlinetoc'));
            $html.= '</div>'.DOKU_LF;
            $html.= '<!-- INLINETOC END -->'.DOKU_LF;
        }
        return $html;
    }

    /**
     * Callback for html_buildlist called from $this->html_inlinetoc()
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
