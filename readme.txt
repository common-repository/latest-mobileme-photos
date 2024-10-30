=== Latest MobileMe Photos ===
Contributors: dertranszendente
Tags: gallery, mobileme, widget, plugin, photos
Requires at least: 2.5
Tested up to: 2.7.1
Stable tag: 0.7

This Plugin displays your latest photos uploaded to your MobileMe Webgallery.

== Description ==

The plugin checks your MobileMe Webgallery feed and displays thumbnails of photos that have recently been uploaded or changed.
The thumbnails link to the fullsize versions in the webgallery. 
Only public photos are displayed (those accessible via feed).

New in this Version:
Fixed feed loading problems.

== Installation ==

1. Upload the `latest-mobileme-photos` folder to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' Menu
1. Add the 'Latest MobileMe Photos' Widget to your Site and enter your MobileMe Username in the Widget Settings

== Frequently Asked Questions ==

= Will I need to enter my MobileMe password? =

No. Only your username is required to get the public photos.

== Requirements ==

* wordpress theme with widget support

== Changelog ==

= Version 0.7 =
* Corrected Feed Urls to fix a problem where the feed could not be loaded on some servers. Plugin should work now for most users.

= Version 0.4 =
* PHP4 Support
* Useses the Magpie library included in Wordpress to fetch feeds
