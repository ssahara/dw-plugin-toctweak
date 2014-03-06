/*
 * DokuWiki plugin TOC Tweak;
 */
jQuery(function() {
    // toc closetoc component
    if (JSINFO.plugin_toc.initial_state == -1) {
        jQuery('#dw__toc .toggle')[0].setState(-1);
    }
});
