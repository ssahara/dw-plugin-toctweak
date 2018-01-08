<?php
/**
 * TocTweak plugin for DokuWiki; Action closetoc
 * set toggle state initially closed (by script.js)
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class action_plugin_toctweak_closetoc extends DokuWiki_Action_Plugin {

    // register hook
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, '_exportToJSINFO');
    }

    /**
     * Exports configuration settings to $JSINFO
     */
    public function _exportToJSINFO(Doku_Event $event) {
        global $JSINFO, $INFO, $ACT;
        // TOC control should be changeable in only normal page
        if (( empty($ACT) || ($ACT=='show') || ($ACT=='preview')) == false) return;

        if (!isset($INFO['meta']['toc']['initial_state'])) {
            $meta_tocInitialState = 1; // open state
        } else {
            $meta_tocInitialState = $INFO['meta']['toc']['initial_state'];
        }
        $JSINFO['toc'] = array(
                'initial_state' => $meta_tocInitialState,
        );
    }

}
