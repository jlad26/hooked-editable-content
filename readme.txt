=== Hooked Editable Content ===
Contributors: janwyl, freemius
Tags: filter, action, hook, editor, add slider, add content, modify theme, customize theme, modify template, customize template
Requires at least: 4.7
Tested up to: 5.5
Stable tag: 1.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create WP or text editors on Edit Post and Edit Page screens for content to be hooked into actions and filters.

== Description ==

An easy way to add content to actions or filters in your theme - and allow users to edit the hooked content by page and post if you wish.

= Example uses =

* Add a slider or image gallery to your site just below the main navigation menu and have the images varying on different pages. Or maybe have some pages without any images at all.
* Add text in the site footer on some pages, but not on all pages. Or maybe different text on different pages.
* Add an additional editor to all pages / posts that allows authors / editors to add in a banner notice to any given page / post if they wish.

You can also see some live [explanatory examples](http://www.sneezingtrees.com/plugins/hooked-editable-content/examples/).

= How it works =

1. Work out where you want to add some content. If your theme already has a suitable action / filter, then great. (Themes like WooCommerce Storefront come with loads.) If not, you can add an action / filter yourself using a child theme.
1. Create a "hooked editor" using the plugin, specifying the action / filter you want to hook the editor to, the type of editor, and which roles can add content.
1. If you wish you can add "generic" content to that hook, which will appear on every page and post.
1. An editor now also appears on every Edit page and Edit post screen for those users with the right role, so "specific" content can be added which will only appear on that page / post.
1. Content saved against a post / page is now displayed on the front-end wherever the action / filter appears. If generic content has been saved, then any specific content replaces it on that page / post only.

== Installation ==

* Download, unzip and upload the plugin folder to the `/wp-content/plugins/` directory
* Activate the plugin through the 'Plugins' menu in WordPress

You should now find the 'Hooked Editors' menu beneath 'Pages' in your Admin menu.

== Frequently Asked Questions ==

= Can I see some examples? =

Check out a few examples with explanation [here](http://www.sneezingtrees.com/plugins/hooked-editable-content/examples/).

= Can I style the output from a specific hooked editor? =

For hooked editors that are hooked to an action, yes. For those hooked to a filter, no.

The output from actions is wrapped in a div with classes `hec-content` and `hec-content-[Hooked editor title]` where [Hooked editor title] is converted to lower case and spaces have been replaced by hyphens.

So for example, if the hooked editor has the title 'Site notice', then you can target the output container using the css selector .hec-content-site-notice.

= Can I disable the display of hooked content without losing the data? =

Yes. Use the 'Hide Hooked Content' options for an editor. You can disable generic content, specific content, or both. The content will not be displayed on the front end.

= How should I choose between using a WP Editor or a text editor? =

It depends on the circumstances.

The WP Editor allows full html and shortcodes just like the normal post / page editor, while the text editor strips out any html, line breaks, etc. So text editor is probably best when you want to limit users to plain text content.

The full WP editor will generally be fine with an action, but filters might be a bit trickier. For example, if your theme outputs the filter like this `echo esc_html( apply_filters( 'some_filter', $output ) );` then any html will be escaped. So unless you want to display escaped html, you're better off preventing any user confusion by choosing the text editor.

= How do I change the order in which hooked editors are displayed when editing pages / posts? =

Click on 'Hooked Editors' in the main menu, then click on 'Re-order hooked editors' just below the main heading 'Editors' at the top of the screen. You can now drag the rows of the table into the order you want.

= Can I change who can add and edit hooked editors? =

Yes. By default, only administrators can add, edit and delete hooked editors. The plugin uses the following custom capabilities, all of which are added only to administrators:

edit_hec_hooks, edit_others_hec_hooks, publish_hec_hooks, read_private_hec_hooks, delete_hec_hooks, delete_private_hec_hooks, delete_published_hec_hooks, delete_others_hec_hooks, edit_private_hec_hooks, edit_published_hec_hooks.

You can add some or all of these capabilities to other roles or users. Note that on deactivation the plugin only removes these custom capabilities automatically from the administrator role. If you add capabilities to other users or roles they will not be removed automatically.

= Can I prevent / enable a hooked editor appearing on certain post types? =

Yes, by selecting or de-selecting the post type in the 'Included post types' meta box when editing the hooked editor. You can also filter the included post types using the filter `hec_included_post_types`:

`
function mytheme_include_my_cpt( $included_post_types, $hooked_editor, $hooked_editor_info ) {
	$included_post_types[] = 'my_custom_post_type';
	return $included_post_types;
}
add_filter( 'hec_included_post_types', 'mytheme_include_my_cpt', 10, 3 );
`

= What happens if I change theme? =

If you switch to a new theme you won't lose any of the hooked content, but it will be hooked to actions / filters in the old theme, so it won't display to start with.

You will need to update the settings of your hooked editors so that they are hooked to actions / filters in your new theme. Note that you can hook an editor to more than one hook, so if you're switching between themes a lot for some reason, you could hook an editor to one hook in one theme and an equivalent hook in another.

== Changelog ==

= 1.1.2 =

Release date: 19 October 2020

* Bug fix: Fix tinyMCE adding p tags even when wpautop setting is disabled.

= 1.1.1 =

Release date: 01 October 2020

* Bug fix: Remove undefined function causing fatal error.

= 1.1.0 =

Release date: 01 October 2020

* Bug fix: Make sure Hooked Content Editors are displayed with block editor when on edit post.
* Feature: Change from defining excluded post types to included post types
* Feature: Add hec_display_hook_content filter so that display on individual pages / archives can be turned on / off
* Maintenance: Don't remove whitespace when sanitizing hooked text editor content.
* Maintenance: Make enqueueing of scripts reliable.
* Maintenance: Remove non-functioning sorting of editors by column.

= 1.0.3 =

Release date: 31 July 2017

* Feature: Add ability to disable wpautop on hooked editors.

= 1.0.2 =

Release date: 24 July 2017

* Maintenance: Update to latest version of Admin Notice Manager.
* Maintenance: Add in rating request.

= 1.0.1 =

Release date: 15 July 2017

* Feature: Add messages to hooked editors on all edit post / page screens to show whether hook is firing on that specific post / page.
* Maintenance: Add in [Freemius](https://freemius.com/wordpress/insights/) functionality for feedback.

= 1.0.0 =

Release date: 27 June 2017

* Original release.
