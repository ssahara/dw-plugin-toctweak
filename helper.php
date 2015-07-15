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

}

