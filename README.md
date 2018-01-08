TocTweak plugin for DokuWiki
====================================

This plugin imlements **METATOC** syntax family that render tailored table of contents (TOC) block inside page content using metadata.  METATOC can be used multiple times in a page, especially for long and complex page where each METATOC may be a headline list of sub-sections under relevant parent chapter. Another use case, for example, an introduction page may have METATOC as link lists that will navigate to relevant page/section where provides detail description.

In order to implement METATOC feature, TocTweak plugin (version 2) will store all headlines found in the page (even if some of them are not shown in TOC box) to metadata, and render TOC/METATOC afterwards. This approach is different from DokuWiki's auto-TOC that is created using predifined global parameters (toptoclevel and maxtoclevel), metadata of the page does not always hold all headlines that may potentially be referred from other page contains METATOC syntax.

MEATOC concept may be used as a supplement of DokuWiki auto-TOC, and this plugin provides TOC macro that enables page-based control of its content.

Usage / Examples
------
#### Control macro for DokuWiki built-in TOC

    ~~CLOSETOC~~         Let the TOC box initially closed
    ~~TOC 2-3~~          Headlines within level 2 to 3 will appear in the TOC box
    ~~NOTOC 2-3~~        No TOC box on the page, but set headline level parameter
    ~~TOC 2-3 wide~~     Widen the TOC box by assigning "wide" css class
    ~~TOC_HERE 2-3~~     Locate the TOC box where the macro is placed in the page

* The built-in toc box (auto-TOC) should be one per page, therefore more than once `~~TOC_HERE~~` will be ignored.
* Headline level parameter must be *n-m*, *n*  or *-m*.

#### METATOC: another method to render Table of Contents

METATOC syntax variants show headline list of current page in different looks/design.

    {{METATOC}}         headline list with hierarchical numbers
    {{TOC}}             similar with DW built-in TOC box without open/close feasure
    {{INLINETOC}}       headline list in rounded box
    {{SIDETOC}}         dedicated to use in sidebar page

METATOC syntax family can render TOC of other page as well, with specified headline level range, starting section, and toc box title.

    {{METATOC 3-3 >:wiki:syntax#Text Conversions | Text Conversions}}
    {{METATOC 3-3 >:wiki:syntax#Text Conversions |}}
    {{METATOC 2-3 >#section title}}
    {{METATOC 2-3}}

* Headline level parameter (*n-m*) must be given before ">". 
* Blank TOC title (given after "|") will show only list of headlines without title – "Table of Contents".
* SIDETOC ignores *n-m* parameter,  which will be retrieved from metadata of current page.

----
Licensed under the GNU Public License (GPL) version 2

More infomation is available:
  * http://www.dokuwiki.org/plugin:tweaktoc

(c) 2014-2018 Satoshi Sahara \<sahara.satoshi@gmail.com>
