/*
 * DokuWiki plugin TOC Tweak;
 */
jQuery(function() {
    if (typeof(JSINFO.toc) != 'undefined') {
        var $toc = jQuery('#dw__toc h3');
        if ($toc.length) {
            $toc[0].setState(JSINFO.toc.initial_state);
        }
    }
});
