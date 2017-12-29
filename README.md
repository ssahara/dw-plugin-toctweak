DokuWiki plugin TOC Tweak
====================================

This plugin imlements **METATOC** syntax family that render tailored table of contents (TOC) block inside page content using metadata. It is a tree of headlines of specified page or those under specided section.
METATOC can be used multiple times in a page, especially for long and complex page where each METATOC may be a headline list of sub-sections under relevant parent chapter.

METATOC can be a part of headline list of other page that have own TOC inside. For example, an introduction page may have METATOC as link lists that will navigate to relevant page/section where provides detail description.

In order to implement/enable METATOC feature, TOC Tweak plugin (version 2) will store all headlines of the page (even if some of them are not shown in TOC box) to metadata, and render METATOC afterwards. This approach is different from DokuWiki's auto-TOC that is created using predifined global parameters (toptoclevel and maxtoclevel), metadata of the page does not always hold all headlines that may potentially be referred from other page contains METATOC syntax.

MEATOC concept may be used as a supplement of DokuWiki auto-TOC, and this plugin provides TOC macro that enables page-based control of its content.

Usage / Examples
------
#### Control macro for DokuWiki built-in TOC

    ~~CLOSETOC~~         Let TOC box initially closed
    ~~TOC 2-3~~          Headlines within level 2 to 3 range are picked up in the TOC
    ~~NOTOC 2-3~~        No TOC box on the page, but set headline level parameters
    ~~TOC 2-3 wide~~     Widen TOC box by assigning "wide" css class
    ~~TOC_HERE 2-3~~     TOC box will appear where the macro is placed in the page

The built-in toc box should be one per page, therefore more than once `~~TOC_HERE~~` will be ignored.


#### METATOC: another method to render Table of Contents

METATOC syntax variants show headline list of current or specified page in different looks/design.

    {{TOC 1-2}}
    {{TOC 3-3 >:wiki:syntax#Text Conversions | Text Conversions}}
    {{INLINETOC 2-2 >:wiki:syntax}}
    {{METATOC}}
    {{SIDETOC}}


----
Licensed under the GNU Public License (GPL) version 2

More infomation is available:
  * http://www.dokuwiki.org/plugin:tweaktoc

(c) 2014-2017 Satoshi Sahara \<sahara.satoshi@gmail.com>
