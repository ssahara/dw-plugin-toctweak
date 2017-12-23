<?php
/**
 * DokuWiki plugin TOC Tweak;
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

            // get TOC generation parameters, like "toptocleevl"-"maxteclevel"
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

}

