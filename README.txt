=== Eclipse Toolbox ===
Contributors: CraigStanfield
Donate link: http://eclipse-creative.com/
Tags: link validation, SEO Tools
Requires at least: 3.0.1
Tested up to: 4.7.3
Stable tag: 4.7.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Eclipse Toolbox handles SEO issues we have found as a DDA. it handles broken links and redirection.

== Description ==

> **[Silver Bullet Pro](https://eclipse-creative.com "Take your business to new heights")** |

Welcome to the Eclipse Toolbox Plugin for Wordpress by Eclipse Creative Consultants Ltd. This plugin is intended to plug
the gaps left by other SEO plugins (We have Yoast on almost all our sites) This plugin is still in the early days of
it's life and new features are expected to be added quickly.

Eclipse Toolbox primary function is link discovery and validation. Any broken links are handled by either replacing with
a # or a specified link. Any links added to a page are automagically discovered and added to the list of links. The
plugin is also able to email a specified contact if things go wrong and will email our development team the error report
so the issue can be resolved. Eclipse Toolbox is also a redirection plugin as broken links are redirected.

Eclipse Plugin is used to redirect a once active url if its status is saved as draft. or if it is deleted (wip)

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `link-validate.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Click the Eclipse Toolbox menu option and click the rebuild tab.
4. wait for it to finish, the page is then reloaded and any broken links are displayed.

== Frequently Asked Questions ==

= The Eclipse Toolbox reports Existing broken links (0) =

You need to rebuild the links table, during development we decided that it may not necessarily be the case that a full
rebuild on activation is ideal so we decided a manual build would suit our clients better.

= What does 'This link is still being used, manually repair the link (shown on page {some url})in its location before
removing broken link again!' mean? =

Although this link is working it is still referred to by the url referenced, you will need to edit that page and change
the link to a valid one or remove the reference, once done the link can be removed.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==
= 1.1.0 =
* uses github to update the plugin.

= 1.0.5 =
* h tag count issue table
* name changed to Eclipse SEO Toolbox

= 1.0.4 =
* Redirects when putting posts into draft and disable when published

= 1.0.3 =
* Refactoring to deal with 500 Server error due to script timeout.
* Incorrect total of links caused by timeout.
* Now uses $wpdb->prefix to correctly define the table
* Refactored link finding algorithm so not as strict, now throws issues for anything dubious or in need of refactoring

= 1.0.2 =
* Added fallback column so a fallback url instead of # can be used in future
* Working percent when 0 fix

= 1.0.1 =
* Added depth field to optimize calls to pages.
* Ability to delete broken links if they aren't still in use. Alerts if still found so issue can be fixed.

= 1.0.0 =
* THis is the first release of this plugin.

== Upgrade Notice ==

= 1.0.3 =
This upgrade is a server compatibility fix. You should upgrade if your links won't build and cause a timeout error.

= 1.0.2 =
This upgrade addresses some bugs discovered. YOu should upgrade immediately

= 1.0.1 =
This upgrade addresses performance issues. You should upgrade as soon as possible.

= 1.0.0 =
This is the release.

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.

== Eclipse Toolbox ==

Features:

1. Link Finder
2. Update Link list on post_type save or create
3. Ability to rebuild if required
4. Possibility to optimize speeds from settings (defaults work well however).
5. Repair broken links
6. Cron to update status on existing links (every 28 days)
7. Display pages with h tag issues (count h1 = 1 h2 > 0 etc)

Upcoming features:

 1. Find all pages within pagination.
 2. handle external links better and find rewrite bounce rate
 3. Remove links that have been fixed
 4. Optimize link finding algorithm to avoid multi testing the same page
 5. Optimize link insertion so it doesn't hang remote end.












Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`
