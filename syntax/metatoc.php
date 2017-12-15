<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak metatoc
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class syntax_plugin_toctweak_metatoc extends DokuWiki_Syntax_Plugin {

    protected $mode;
    protected $pattern = array(
        5 => '{{METATOC:?.*?}}',  // DOKU_LEXER_SPECIAL
    );
    protected $tocStyle = 'toc_hierarchical'; // default toc visual design

    function __construct() {
        $this->mode = substr(get_class($this), 7); // drop 'syntax_' from class name
    }

    function getType() { return 'substition'; }
    function getPType(){ return 'block'; }
    function getSort() { return 30; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->pattern[5], $mode, $this->mode);
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        global $ID;

        // load helper object
        $helper = $helper ?: $this->loadHelper($this->getPluginName());

        // disable built-in TOC display
        //$handler->_addCall('notoc', array(), $pos);

        // Ex: {{METATOC>id#section 2-4 width18 toc_hierarchical}}

        $start = strpos($this->pattern[5],':');
        if ($match[$start] == '>') {
            list($id, $param) = explode(' ', substr($match, $start+1, -2), 2);
            list($page, $section) = explode('#', $id, 2);
            $page = $page ?: $ID;
            $id = $page.(empty($section) ? '' : '#'.$section);
        } else {
            $id = $ID;
            $param = substr($match, $start+1, -2);
        }

        list($topLv, $maxLv, $tocClass) = $helper->parse($param);

        // check basic tocStyle
        if (!preg_match('/\btoc_.*\b/', $tocClass)) {
            $tocClass = implode(' ', array($this->tocStyle, $tocClass));
        }

        $data = array($id, $topLv, $maxLv, $tocClass);
        return $data;
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        global $INFO, $conf, $lang;

        list($id, $topLv, $maxLv, $tocClass) = $data;
        list($id, $section) = explode('#', $id);

        switch ($format) {
            case 'metadata':
                if ($id != $INFO['id']) { // current page
                    // prepare dependency info for PARSER_CACHE_USE event handler
                    $renderer->meta['relation']['toctweak'][] = metaFN($id,'.meta');
                }
                return true;

            case 'xhtml':
             // $renderer->info['cache'] = false; // disable xhtml cache

                // retrieve TableOfContents from metadata
                $toc = $this->get_metatoc($id, $topLv, $maxLv, $section);
                if (empty($toc)) {
                    $toc[] = array(  // error entry
                        'hid'   => $section,
                        'page'  => $id,
                        'url'   => wl($id.'#'.$section),
                        'class' => 'wikilink2',
                        'title' => $id.'#'.$section,
                        'type'  => 'ul',
                        'level' => 1, 
                    );
                }

                // toc wrapper attributes
                $attr['class'] = $tocClass;

                $html = '<!-- METATOC START -->'.DOKU_LF;
                $html.= '<div '.buildAttributes($attr).'>';
                $html.= '<h3>'.$lang['toc'].'</h3>';
                $html.= html_buildlist($toc, 'toc', array($this, 'html_list_metatoc'));
                $html.= '</div>'.DOKU_LF;
                $html.= '<!-- METATOC END -->'.DOKU_LF;

                $renderer->doc .= $html;
                return true;
        }
    }

    /**
     * Callback for html_buildlist called from $this->render()
     * Builds html of each list item
     */
    function html_list_metatoc($item) {
        $html = '<span class="li">';
        if (isset($item['page'])) {
            $html.= '<a title="'.$item['page'].'#'.$item['hid'].'"';
            $html.= ' href="'.$item['url'].'" class="'.$item['class'].'">';
        } else {
            $html.= '<a href="#'.$item['hid'].'">';
        }
        $html.= hsc($item['title']).'</a>';
        $html.= '</span>';
        return $html;
    }

    /**
     * Get cuaatomized toc array using metafata of the page
     */
    function get_metatoc($id, $topLv=null, $maxLv=null, $headline='') {
        global $ID, $conf;
        $topLv = isset($topLv) ? $topLv : $this->getConf('toptoclevel');
        $maxLv = isset($maxLv) ? $maxLv : $this->getConf('maxtoclevel');
        $headline_matched = empty($headline);
        $headline_level   = null;

        $toc = array();

        // retrieve TableOfContents from metadata
        $items = p_get_metadata($id,'description tableofcontents');
        if ($items == null) return $toc;

        foreach ($items as $item) {
            // skip non-interested toc entries
            if ($headline) {
                if (!$headline_matched) {
                    if ($item['hid'] == $headline) {
                        $headline_matched = true;
                        $headline_level = $item['level'];
                    } else {
                        continue;
                    }
                } else {
                    if ($item['level'] <= $headline_level) continue;
                }
            }

            // get headline level in real page
            $Lv = $item['level'] + $conf['toptoclevel'] -1;
            // get depth in metatoc
            $tocLv = $Lv - $topLv +1;

            // exclude out-of-range item based on headline level
            if (($Lv < $topLv)||($Lv > $maxLv)) {
                continue;
            }

            // interested toc entry
            $item['level'] = $tocLv;

            // add properties for toc of that is not current page
            if ($id != $ID) {
                // headlines should be found in other wiki page
                $item['page']  = $id;
                $item['url']   = wl($id.'#'.$item['hid']);
                $item['class'] = 'wikilink1';
            } else {
                // headlines in current page (internal link)
                $item['url']  = '#'.$item['hid'];
            }
            $toc[] = $item;
        }
        return $toc;
    }
}
