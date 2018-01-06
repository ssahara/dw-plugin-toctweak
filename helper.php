<?php
/**
 * DokuWiki plugin TOC Tweak; helper component
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */
if(!defined('DOKU_INC')) die();

class helper_plugin_toctweak extends DokuWiki_Plugin {

    /**
     * overwrite Hash data defined in plugin.info.txt
     */
    function getPluginInfo(array $arrHash) {
        $pluginInfoTxt = DOKU_PLUGIN.$this->getPluginName().'/plugin.info.txt';
        return array_merge(confToHash($pluginInfoTxt), $arrHash);
    }

    /**
     * syntax parser
     */
    function parse($param) {

        // Ex: {{METATOC 2-4 width18 toc_hierarchical >id#section | title}}

        // get tocTitle
        if (strpos($param, '|') !== false) {
            list($param, $tocTitle) = explode('|', $param);
            // empty tocTitle will remove h3 'Table of Contents' headline
            $tocTitle = trim($tocTitle); 
        } else {
            $tocTitle = null;
        }

        // get id#section
        list($param, $id) = explode('>', $param, 2);
        list($id, $hash) = array_map('trim', explode('#', $id, 2));
        $id = cleanID($id).($hash ? '#'.$hash : '');

        // get other parameters
        $params = explode(' ', $param);
        foreach ($params as $token) {
            if (empty($token)) continue;

            // get TOC generation parameters, like "toptocleevl"-"maxtoclevel"
            if (preg_match('/^(?:(\d+)-(\d+)|^(\-?\d+))$/', $token, $matches)) {
                if (count($matches) == 4) {
                    if (strpos($matches[3], '-') !== false) {
                        $maxLv = abs($matches[3]);
                    } else {
                        $topLv = $matches[3];
                    }
                } else {
                        $topLv = $matches[1];
                        $maxLv = $matches[2];
                }

                if (isset($topLv)) {
                    $topLv = ($topLv < 1) ? 1 : $topLv;
                    $topLv = ($topLv > 5) ? 5 : $topLv;
                } else {
                    $topLv = $this->getConf('toptoclevel');
                }

                if (isset($maxLv)) {
                    $maxLv = ($maxLv > 5) ? 5 : $maxLv;
                } else {
                    $maxLv = $this->getConf('maxtoclevel');
                }
                continue;
            }

            // get class name for TOC box, ensure excluded any malcious character
            if (!preg_match('/[^ A-Za-z0-9_-]/', $token)) {
                $classes[] = $token;
            }
        }
        if (!empty($classes)) {
            $tocClass = implode(' ', $classes);
        } else {
            $tocClass = null;
        }

        return array($topLv, $maxLv, $tocClass, $tocTitle, $id);
    }

    /**
     * Get customized toc array using metadata of the page
     */
    function get_metatoc($id, $topLv=null, $maxLv=null, $headline='') {
        global $ID, $INFO;

        // retrieve TableOfContents from metadata
        if ($id == $INFO['id']) {
            $toc = $INFO['meta']['description']['tableofcontents'];
        } else {
            $toc = p_get_metadata($id,'description tableofcontents');
        }
        if ($toc == null) return array();

        // get interested headline items
        $toc = $this->_toc($toc, $topLv, $maxLv, $headline);

        // modify toc array items directly within loop by reference
        foreach ($toc as &$item) {
            // add properties for toc of that is not current page
            if ($id != $ID) {
                // headlines should be found in other wiki page
                $item['page']  = $id;
                $item['url']   = wl($id).'#'.$item['hid'];
                $item['class'] = 'wikilink1';
            } else {
                // headlines in current page (internal link)
                $item['url']  = '#'.$item['hid'];
            }
        } // end of foreach
        unset($item); // break the reference with the last item
        return $toc;
    }

    /**
     * toc array filter
     */
    function _toc(array $toc, $topLv=null, $maxLv=null, $headline='') {
        global $conf;
        $topLv = isset($topLv) ? $topLv : $this->getConf('toptoclevel');
        $maxLv = isset($maxLv) ? $maxLv : $this->getConf('maxtoclevel');

        $headline_matched = empty($headline);
        $headline_level   = null;
        $items = array();

        foreach ($toc as $item) {
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
                    if ($item['level'] <= $headline_level) {
                        $headline_matched = false;
                        $headline_level = null;
                        continue;
                    }
                }
            }

            // get headline level in real page
            $Lv = $item['level'] + $conf['toptoclevel'] -1;

            // exclude out-of-range item based on headline level
            if (($Lv < $topLv)||($Lv > $maxLv)) {
                continue;
            } else { // interested toc entry
                $item['level'] = $Lv - $topLv +1;
            }
            $items[] = $item;
        }
        return $items;
    }

    /**
     * convert auto-toc array to XHTML tailored with class attibute
     */
    function html_toc(array $toc, ...$params) {
        global $INFO;
        $meta =& $INFO['meta']['toc'];

        if (count($params)) {
            // apply toc array filter
            list($topLv, $maxLv, $headline) = $params;
            $toc = $this->_toc($toc, $topLv, $maxLv, $headline);
        }

        $html = html_TOC($toc); // use function in inc/html.php
        if ($html && isset($meta['class'])) {
            $search =  '<div id="dw__toc"';
            $replace = $search.' class="'.hsc($meta['class']).'"';
            $html = str_replace($search, $replace, $html);
        }
        return $html;
    }

}

