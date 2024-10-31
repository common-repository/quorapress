=== QuoraPress ===
Contributors: jinxcode
Donate link: http://www.jinxcode.com
Tags: quora, rss
Requires at least: 3.0
Tested up to: 3.0
Stable tag: 0.2 

QuoraPress displays posts you make on Quora directly on your Wordpress blog.

== Description ==

Display the latest posts from your Quora profile. Integrates easy with your theme.

== Installation ==

1. Upload quorapress.php to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `quorapress_show("answers",10);` in your templates
1. If you prefer you can use short tags directly in your posts or pages: [quora type="answers" items="10"]

== Frequently Asked Questions ==

= How do i display the questions i have posted on Quora =

Simply paste quorapress_show("answers",10);  in a suitible location in your theme
you can change "questions" to "answers" to display posts you answered to.

You can also use a short tag directly in your posts or pages with the following syntax:
[quora type="answers" items="10"]

== Screenshots ==


== Changelog ==

= 0.2 =
* Added support for short tags

= 0.1 =
* First release

== Upgrade Notice ==



