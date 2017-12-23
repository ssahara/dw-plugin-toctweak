DokuWiki plugin TOC Tweak
====================================

TOC Tweaking assortment

Collection of syntax components to tweak table of contents (TOC) for specific pages. 
Allow to tune TOC property: 

1. initial toggle status, 
2. top and max level of headings, 
3. position with css class.

Usage
------
#### Control macro for DokuWiki built-in TOC

    ~~CLOSETOC~~         Let TOC box initially closed
    ~~TOC 2-3~~          Headlines within level 2 to 3 range are picked up in the TOC
    ~~TOC_HERE 2-3~~     TOC box will appear where the macro is placed in the page

The built-in toc box should be one per page, therefore more than once `~~TOC_HERE~~` will be ignored.


#### Additional methods to show TOC (in different looks/design)

    {{TOC 1-2}}
    {{TOC 3-3 >:wiki:syntax#Text Conversions | Text Conversions}}
    {{INLINETOC 2-2 >:wiki:syntax}}
    {{METATOC}}


----
Licensed under the GNU Public License (GPL) version 2

More infomation is available:
  * http://www.dokuwiki.org/plugin:tweaktoc

(c) 2014-2017 Satoshi Sahara \<sahara.satoshi@gmail.com>
