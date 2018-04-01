<?php
/*
 * TocTweak plugin for DokuWiki;
 */

$conf['tocAllHeads'] = 1;
$conf['tocPosition'] = 0;
$conf['tocState']    = 1;

// Takeover TOC settings from main process
// @see DOKU_INC/conf/dokuwiki.php
$conf['toptoclevel'] = 1;
$conf['maxtoclevel'] = 3;
$conf['tocminheads'] = 3;

