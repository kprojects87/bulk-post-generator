# Bulk Post Generator

Generate 6 blog post drafts at a time from a clean, modern WordPress admin
dashboard — no API key, account, or external AI service required.

## Features

- **One-click batches** — enter a topic/niche, click *Generate 6 Posts*, get
  6 (configurable, 1–10) WordPress drafts with titles, content, and excerpts.
- **Business-aware content** — optionally set a business name and industry
  so titles and closing paragraphs feel relevant to that business.
- **Optional featured images** — attach a free stock photo to each post
  automatically, no API key needed.
- **Fully offline** — everything is generated locally from built-in
  templates. Nothing is sent to any external AI service.
- **Modern admin UI** — a tabbed, card-based dashboard (Generate / History /
  Settings) with a live progress bar as each post is created.

## Installation

1. Download or clone this repo into `wp-content/plugins/bulk-post-generator`.
2. Activate **Bulk Post Generator** from the WordPress Plugins screen.
3. Go to **Post Generator** in the admin sidebar.
4. (Optional) Open **Settings** to enable featured images and set a default
   category, post status, post length, and business name/type.
5. Go to **Generate Posts**, enter a topic, and click **Generate 6 Posts**.

## How it works

1. Templates build 6 distinct post titles from your topic/keywords/business
   info.
2. Each title is expanded into a full HTML draft (intro, 3–5 sections, a
   conclusion, and an excerpt).
3. If enabled, a free stock photo is fetched and set as the featured image.
4. Each post is inserted as a normal WordPress post (draft by default) in
   your chosen category — review and personalize before publishing.

## File structure

```
bulk-post-generator/
├── bulk-post-generator.php      # Plugin bootstrap
├── includes/
│   ├── class-bpg-admin.php      # Admin dashboard, settings, AJAX handlers
│   └── class-bpg-generator.php  # Title/content/image generation
├── assets/
│   ├── css/admin.css
│   └── js/admin.js
└── readme.txt                   # WordPress.org-style readme
```

## Requirements

- WordPress 5.8+
- PHP 7.4+

## License

GPLv2 or later — see [LICENSE](LICENSE).

## Contributing

Issues and pull requests are welcome. This is a small, dependency-free
plugin by design — please keep new features free/offline where possible,
or clearly optional if they require external services.
