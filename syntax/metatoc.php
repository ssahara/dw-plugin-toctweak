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
        5 => '{{(?:METATOC|TOC)\b.*?}}',
    );
    protected $tocStyle = array(  // default toc visual design
        'METATOC' => 'toc_hierarchical',
        'TOC'     => 'toc_dokuwiki',
    );

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
        isset($tocTweak) || $tocTweak = $this->loadHelper($this->getPluginName());

        // Ex: {{METATOC 2-4 width18 toc_hierarchical >id#section | title}}

        preg_match('/^{{([A-Z]+)([>: ]?)/', $match, $m);
        $start = strlen($m[1]) +2;
        $param = substr($match, $start+1, -2);

        list($topLv, $maxLv, $tocClass, $tocTitle, $id) = $tocTweak->parse($param);

        $hash = strstr($id, '#');
        if ($id == $hash) { $id = $ID.$hash; }

        // should disable built-in TOC here?
        if ($m[1] == 'TOC') {
            $handler->_addCall('notoc', array(), $pos);
        }

        // check basic tocStyle
        if (!preg_match('/\btoc_.*\b/', $tocClass)) {
            $tocStyle = $this->tocStyle[$m[1]];
            $tocClass = implode(' ', array($tocStyle, $tocClass));
        }

        $data = array($id, $topLv, $maxLv, $tocClass, $tocTitle);
        return $data;
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        global $INFO, $conf, $lang;

        list($id, $topLv, $maxLv, $tocClass, $tocTitle) = $data;
        list($id, $section) = explode('#', $id);

        switch ($format) {
            case 'metadata':
                if ($id != $INFO['id']) { // current page
                    // set dependency info for PARSER_CACHE_USE event handler
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
                $title = isset($tocTitle) ? $tocTitle : $lang['toc'];

                $html = '<!-- METATOC START -->'.DOKU_LF;
                $html.= '<div '.buildAttributes($attr).'>';
                $html.= $title ? '<h3>'.hsc($title).'</h3>' : '';
                $html.= '<div>';
                $html.= html_buildlist($toc, 'toc', array($this, 'html_list_metatoc'));
                $html.= '</div>';
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
