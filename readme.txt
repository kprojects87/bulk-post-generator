=== Bulk Post Generator ===
Contributors: kprojects87
Tags: bulk posts, post generator, ai content, content generator, blog generator, gemini, artificial intelligence, ai posts
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate multiple WordPress blog posts with AI directly from your WordPress dashboard.

== Description ==

Bulk Post Generator is a WordPress plugin designed to help website owners and content creators generate multiple blog posts efficiently using artificial intelligence.

The plugin integrates AI-powered content generation into the WordPress admin area, allowing users to provide topics and generate WordPress posts without manually creating every article from scratch.

It is designed for websites that need to create blog content at scale while keeping the generated content inside the familiar WordPress publishing workflow.

The plugin can generate post content, assign categories, and work with featured images as part of the content-generation workflow.

== Features ==

* Generate WordPress blog posts using AI.
* Generate multiple posts in bulk.
* Enter topics for AI-powered content generation.
* Generate titles and article content automatically.
* Create posts directly in WordPress.
* Automatically assign generated posts to a selected WordPress category.
* Support for featured images in generated posts.
* AI-powered content generation through the configured API provider.
* Google Gemini API integration.
* Store and manage the AI API key from the plugin settings.
* Generate content from the WordPress admin dashboard.
* Reduce repetitive manual content creation work.
* Integrates with the standard WordPress post system.
* Simple WordPress admin interface.

== AI Content Generation ==

Bulk Post Generator uses an AI content-generation service to create article content based on the topics provided by the user.

To use AI-powered generation, an appropriate API key must be configured in the plugin settings.

The plugin sends the information required to generate the requested content to the configured AI service. Users should review generated content before publishing it.

AI-generated content may require editing, fact checking, formatting, and SEO optimization before publication.

== Featured Images ==

The plugin supports featured-image functionality as part of the post-generation workflow.

Depending on the configured image-generation or image-source functionality, generated posts can have a featured image assigned automatically.

Users should ensure that any external images or image-generation services used with the plugin are properly licensed and permitted for their intended use.

== Requirements ==

* WordPress 5.8 or higher.
* PHP 8.0 or higher.
* An active AI API account and valid API key for AI-powered content generation.
* Internet connectivity for communication with external AI services.
* Sufficient WordPress permissions to create and manage posts.

== Installation ==

1. Download or clone the `bulk-post-generator` plugin.
2. Upload the `bulk-post-generator` folder to the `/wp-content/plugins/` directory.
3. Alternatively, install the plugin through the WordPress Plugins screen.
4. Activate the plugin through the 'Plugins' screen in WordPress.
5. Open the Bulk Post Generator settings from the WordPress admin area.
6. Enter your AI API key.
7. Save the plugin settings.
8. Enter the topics or content requirements for the posts you want to generate.
9. Select the appropriate WordPress category when required.
10. Start the bulk generation process.
11. Review the generated posts before publishing them.

== Frequently Asked Questions ==

= What is Bulk Post Generator? =

Bulk Post Generator is a WordPress plugin that helps generate multiple blog posts using artificial intelligence directly from the WordPress dashboard.

= Does the plugin generate posts automatically? =

Yes. The plugin can generate WordPress posts from topics provided by the user. Generated content should be reviewed before publication.

= Does the plugin support bulk generation? =

Yes. The plugin is designed to generate multiple posts as part of a bulk content-generation workflow.

= Which AI service does the plugin use? =

The plugin supports Google Gemini for AI-powered content generation.

= Do I need an API key? =

Yes. A valid API key for the configured AI service is required for AI-powered generation.

= Where do I add my API key? =

The API key can be configured through the plugin's settings in the WordPress administration area.

= Can generated posts be assigned to a category? =

Yes. Generated posts can be assigned to a selected WordPress category.

= Does the plugin generate featured images? =

The plugin supports featured-image functionality as part of the content-generation workflow. The exact image-generation functionality depends on the image provider configured and supported by the installed version.

= Does the plugin publish posts automatically? =

Generated content should be reviewed before publication. Depending on the configured workflow, posts can be created in WordPress for further review and publishing.

= Can I edit generated content? =

Yes. Generated posts use the standard WordPress post system and can be edited before publication.

= Is the generated content guaranteed to be accurate? =

No. AI-generated content can contain inaccurate, outdated, or incomplete information. Always review and fact-check generated content before publishing it.

= Is an internet connection required? =

Yes. The plugin requires internet connectivity when communicating with external AI services.

= Is this plugin affiliated with Google? =

No. Bulk Post Generator is an independent WordPress plugin. Google Gemini is a third-party service and is not affiliated with or endorsed by this plugin unless explicitly stated.

== Screenshots ==

1. Bulk Post Generator admin dashboard.
2. AI API configuration settings.
3. Bulk post generation interface.
4. Topic and content generation settings.
5. WordPress category selection for generated posts.
6. Generated WordPress post with featured image.

== Privacy ==

Bulk Post Generator may communicate with third-party AI services when AI content generation is requested.

When you use an external AI service, information required to process your generation request may be transmitted to that service.

Users are responsible for reviewing the privacy policy and terms of the third-party services they configure and use with the plugin.

Do not enter confidential, sensitive, or personally identifiable information into AI prompts unless you have confirmed that doing so is appropriate under the applicable privacy policies and terms.

== External Services ==

Bulk Post Generator may use Google Gemini API services to generate AI content.

When AI generation is requested, the plugin communicates with the configured AI service using the API credentials supplied by the website administrator.

The third-party service is subject to its own terms of service and privacy policy.

Service:
Google Gemini API

Purpose:
AI-powered content generation.

Website:
https://ai.google.dev/

Users should review Google's current terms and privacy documentation before using the service.

== Content Disclaimer ==

AI-generated content is provided as an assistance tool and should not be considered guaranteed factual or professional information.

Website owners are responsible for reviewing, editing, fact-checking, and approving generated content before publication.

== Changelog ==

= 1.0.0 =

* Initial release.
* Added AI-powered WordPress post generation.
* Added bulk post generation workflow.
* Added AI API configuration.
* Added Google Gemini API integration.
* Added category assignment for generated posts.
* Added featured-image support.
* Added WordPress admin generation interface.
* Added post creation workflow.

== Upgrade Notice ==

= 1.0.0 =
Initial release of Bulk Post Generator.
