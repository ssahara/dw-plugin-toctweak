/*
 * DokuWiki plugin TOC Tweak;
 */
jQuery(function() {
    // JSINFO.toc is to be defined in the Action toctweak closetoc component
    if (typeof(JSINFO.toc.initial_state) != 'undefined') {
        var $toc = jQuery('#dw__toc h3');
        if ($toc.length) {
            $toc[0].setState(JSINFO.toc.initial_state);
        }
    }
});
