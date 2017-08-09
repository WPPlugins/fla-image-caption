=== fla_Image Caption ===
Contributors: hornament
Donate link: http://flavian.imlig.info/?nav=2&show=1
Tags: image, caption, author, image author
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin to insert image authors automatically.

== Description ==

Plugin accesses the caption shortcode via `add_filter( \'img_caption_shortcode\', ... )`.

By default, image authors are read out of the description of the respective image. Therefore, they have to be tagged in a specific way: 
In the description of the respective image, accessible under 'Media' > 'Edit' > 'Description', the image author (or multiple image authors) have to be wrapped in `<author></author>` tags.

If there are no authors in image description, the plugin tries to read authors from the image's Exif information. If successful, image authors are automatically saved into image description.

== Installation ==

1. Upload `fla_image-caption` folder into the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. (optional) Customize Plugin Options through the respective menu in 'Options'
4. Use [caption] shortcode in your pages and posts

== Frequently Asked Questions ==
none

== Screenshots ==
none

== Changelog ==

= 1.1 =
* Coding paradigm changed to Object-Oriented programming.
* Default Options work on installation without customizing.
* Multiple Authors

== Upgrade Notice ==

= 1.1 =
First version to be highly custumizable