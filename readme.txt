=== Post6WidgetArea ===
Contributors: enomoto celtislab
Tags: dynamic_sidebar, widget-area, widget, category,
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 0.6.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add the widget area of 6 locations around the post article, etc..

== Description ==

You can easily insert before and after the articles, advertising and social button, and boilerplate.

Add 6 widget areas.

 * Start position of the page
 * Before the single post content 
 * Articles in short code or more tag position 
 * After the single post content 
 * End position of the page  
 * wp_head position (In the HTML &lt;head&gt; element)
 

Add Post6 text widget

 * It is a function up version of the text widget.
 * You can specify the lifetime and post ID, category of interest.


These features, making it easy to use for displaying an advertising and social button, and boilerplate.


[日本語の説明](http://celtislab.net/wp_plugin_post6widgetarea/ "Documentation in Japanese")

== Installation ==

1. Upload the `Post6WidgetArea` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Open the setting menu, it is possible to set the execution condition.

== Screenshots ==

1. The widget area configuration menu.
2. 6 widget areas that have been added.
3. post6 text widget setting (Chrome browser when used with HTML5-enabled theme)
4. post6 text widget setting (HTML5 'input type=date' unsupported browser) 

== Changelog ==

= 0.1.0 =
* 2013-03-21  First release
 
= 0.2.0 =
* 2013-03-25 Add options (exclude option setting)

= 0.4.0 =
* 2013-04-05 Internationalization (Japanese translation MO file)

= 0.5.0 =
* 2013-05-09 Add Post6 text widget

= 0.6.0 =
* 2013-07-01 
* Change : Add option (If there is more tag definition, insert there the widget area)
* Change : Enclosed in 'div' tag widget area to be able to customize the CSS 
* Change : delete option (transfer to footer section in extracted script tag)
* Fix : bug fix and Code cleanups

= 0.6.1 =
* 2013-07-04 
* Fix : Remove div tag that you gave to the wp_head part due to an error occurs in the JavaScript in another plugin

= 0.6.2 =
* 2013-10-02 
* Fix : modified so that it does not output div tag, p tag to wp_head part
* Fix : Change to post6widget-area the class name so as not to be affected by the CSS for the widget area in the theme
