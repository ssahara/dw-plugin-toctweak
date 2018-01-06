<?php
/**
 * DokuWiki plugin TOC Tweak; Syntax toctweak sidetoc
 * 
 * provide compatibility for Andreone's inlinetoc plugin
 * @see also https://www.dokuwiki.org/plugin:inlinetoc
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

require_once(dirname(__FILE__).'/metatoc.php');

class syntax_plugin_toctweak_sidetoc extends syntax_plugin_toctweak_metatoc {

    protected $pattern = array(
        5 => '{{SIDETOC\b.*?}}',  // DOKU_LEXER_SPECIAL
    );
    protected $tocStyle = array(  // default toc visual design
        'SIDETOC' => 'toc_shrinken',
    );

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        global $INFO, $ID;

        // disable using cache
        $handler->_addCall('nocache', array(), $pos);

        $data = parent::handle($match, $state, $pos, $handler);
        list($id, $topLv, $maxLv, $tocClass, $tocTitle) = $data;
 
        // sidetoc (in sidebar page) must show toc of different page
        // $id, $topLv, $maxLv dose not appropriate values for sidetoc
        $id = '@ID@';
        $topLv = $maxLv = null;

        // check basic tocStyle
        if (!preg_match('/\btoc_.*\b/', $tocClass)) {
            $tocClass = implode(' ', array($this->tocStyle['SIDETOC'], $tocClass));
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

        $id = $INFO['id'];     // str_replace('@ID@', $INFO['id'], $id);

        // retrieve toc config parameters from metadata
        $meta = p_get_metadata($id, 'toc');
        $topLv = @$meta['toptoclevel'] ?: $this->getConf('toptoclevel');
        $maxLv = @$meta['maxtoclevel'] ?: $this->getConf('maxtoclevel');

        switch ($format) {
            case 'metadata':
                return false;

            case 'xhtml':
                // load helper object
                isset($tocTweak) || $tocTweak = $this->loadHelper($this->getPluginName());

                // retrieve TableOfContents from metadata
                $toc = $tocTweak->get_metatoc($id, $topLv, $maxLv, $section);

                // toc wrapper attributes
                $attr['class'] = $tocClass;
                $title = isset($tocTitle) ? $tocTitle : $lang['toc'];

                $html = '<!-- SIDETOC START -->'.DOKU_LF;
                $html.= '<div '.buildAttributes($attr).'>';
                $html.= $title ? '<h3>'.hsc($title).'</h3>' : '';
                $html.= '<div>';
                $html.= html_buildlist($toc, 'toc', array($this, 'html_list_metatoc'));
                $html.= '</div>';
                $html.= '</div>'.DOKU_LF;
                $html.= '<!-- SIDETOC END -->'.DOKU_LF;

                $renderer->doc .= $html;
                return true;
        } // end of switch
        return false;
    }

    /**
     * Callback for html_buildlist called from $this->render()
     * Builds html of each list item
     * In case of {{SIDETOC}}, href attribute of TOC items must be locallink.
     */
    function html_list_metatoc($item) {
        $html = '<span class="li">';
        $html.= '<a href="#'.$item['hid'].'">';
        $html.= hsc($item['title']).'</a>';
        $html.= '</span>';
        return $html;
    }

}
