<?php
/*
 * TocTweak plugin for DokuWiki;
 */

$meta['tocAllHeads'] = array('onoff');
$meta['tocPosition'] = array('multichoice','_choices' => array(0,1,2,6,9));
$meta['tocState']    = array('multichoice','_choices' => array(-1,0,1));

// Takeover TOC settings from DokuWiki Configuration Manager
// @see DOKU_INC/lib/plugins/config/settings/config.metadata.php
$meta['toptoclevel'] = array('multichoice','_choices' => array(1,2,3,4,5));
$meta['maxtoclevel'] = array('multichoice','_choices' => array(0,1,2,3,4,5));
$meta['tocminheads'] = array('multichoice','_choices' => array(0,1,2,3,4,5,10,20,50,100));

