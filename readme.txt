=== Bulk Post Generator ===
Contributors: yourname
Tags: content generation, blog posts, templates, drafts, featured images
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 4.0.0
License: GPLv2 or later

Generate 6 blog post drafts at a time from a clean, modern admin dashboard —
no API key, account, or external AI service required.

== Description ==

Bulk Post Generator adds a dedicated admin dashboard where you enter a topic/
niche, optional keywords, and (optionally) a business name and type. Click one
button and it:

1. Builds 6 (configurable, 1-10) distinct post titles from built-in templates.
2. Writes a full HTML-formatted draft and a short excerpt for each title.
3. Optionally adds a free stock photo as each post's featured image.
4. Inserts each as a WordPress post (draft/pending/publish, your choice) in
   your default category.
5. Shows a live progress bar and links straight to each new draft.

Everything runs locally — there's no API key, no account, no external AI
service, and no per-post cost. Content is template-based and meant as a
structural starting point (headings, intro, sections, conclusion); review
and personalize before publishing.

== Featured Images ==

Turn on "Add a featured image to each generated post" in Settings and each
post gets a real photo automatically attached and set as its featured image
— pulled from a free, no-key-required stock photo service, added straight to
your Media Library.

== Installation ==

1. Upload the `bulk-post-generator` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to "Post Generator" in the admin sidebar.
4. (Optional) Open Settings to enable featured images, and set a default
   category, post status, post length, and business name/type.
5. Go to the Generate Posts tab, enter a topic, and click "Generate 6 Posts".

== Frequently Asked Questions ==

= Does this need an API key? =
No. All content and images are generated/fetched from free, no-key-required
sources — nothing is sent to any paid AI service.

= Where do the generated posts (and images) go? =
Posts are inserted as normal WordPress posts (default: Draft status) so you
can review, edit, and publish them from the regular Posts screen. Generated
images are added to your Media Library and set as each post's featured image.

= Can I generate more or fewer than 6 at a time? =
Yes — the batch size field on the Generate Posts tab accepts 1-10, with 6 as
the default.

= Can I choose a category per batch? =
Posts use the default category set on the Settings tab. Change it there any
time; new batches pick up the change automatically.

== Changelog ==

= 4.0.0 =
* Removed the Google Gemini integration. The plugin is now fully offline —
  templates only, no external AI API of any kind.
* Featured images now use a free stock-photo fallback for all users.

= 3.0.0 =
* (Removed in 4.0.0) Added Google Gemini as a content engine.

= 2.0.0 =
* Removed legacy OpenAI/Anthropic integrations in favor of a simpler,
  fully offline template mode.
* Removed the per-batch category selector; posts now use the Settings default.

= 1.0.0 =
* Initial release.
