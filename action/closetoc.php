<?php
/**
 * DokuWiki plugin TOC Tweak; Action toctweak closetoc
 * set toggle state initially closed (by script.js)
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_toctweak_closetoc extends DokuWiki_Action_Plugin {

    // register hook
    public function register(&$controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, '_exportToJSINFO');
    }

    /**
     * Exports configuration settings to $JSINFO
     */
    public function _exportToJSINFO(&$event) {
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
