# Bulk Post Generator

A powerful WordPress plugin for generating and managing bulk blog posts using AI. Designed to help website owners, developers, and content teams create SEO-friendly content efficiently from a WordPress dashboard.

## 🚀 Features

* 🤖 AI-powered blog post generation
* 📝 Generate multiple posts in bulk
* 🏷️ Select and assign WordPress categories
* 🖼️ Generate or assign featured images
* 🔑 Save and manage API credentials from the WordPress admin panel
* ⚙️ Easy-to-use WordPress admin interface
* 📦 Automatic WordPress post creation
* 📌 Support for custom post-generation settings
* 🔄 Generate unique content for each post
* 🧩 Developer-friendly plugin structure

## 📋 Requirements

Before installing the plugin, make sure your server meets the following requirements:

* WordPress 6.0+
* PHP 8.0+
* MySQL 5.7+ / MariaDB 10.4+
* WordPress Administrator access
* Required AI API credentials

## 📥 Installation

### Method 1 — WordPress Dashboard

1. Download or clone this repository.

2. Create a ZIP file of the `bulk-post-generator` folder.

3. Go to:

   **WordPress Dashboard → Plugins → Add New → Upload Plugin**

4. Upload the plugin ZIP file.

5. Click **Install Now**.

6. Activate the plugin.

### Method 2 — Manual Installation

Copy the plugin folder to:

```text
/wp-content/plugins/bulk-post-generator/
```

Then activate it from:

```text
WordPress Dashboard → Plugins
```

## ⚙️ Configuration

After activating the plugin:

1. Open the **Bulk Post Generator** section in the WordPress admin dashboard.
2. Enter the required API credentials.
3. Configure your content generation settings.
4. Select the desired category.
5. Configure the number of posts you want to generate.
6. Start the generation process.

## ✍️ Content Generation

The plugin can be used to generate blog content based on your selected business type, topic, category, and other generation settings.

Each generated post can be automatically created as a WordPress post and assigned to the selected category.

## 🖼️ Featured Images

The plugin supports featured-image generation/assignment for generated posts.

The goal is to provide each generated article with a unique and relevant featured image instead of repeatedly using the same image.

## 📁 Plugin Structure

```text
bulk-post-generator/
│
├── admin/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
│
├── includes/
│   ├── class-admin.php
│   ├── class-generator.php
│   └── class-gemini.php
│
├── business-content-generator.php
└── README.md
```

## 🔐 API Key Security

API credentials should be treated as sensitive information.

Do not commit API keys, passwords, tokens, or other secrets to GitHub.

Recommended:

```text
API keys → WordPress admin/settings
Source code → GitHub
Secrets → Never commit
```

If you accidentally expose an API key, revoke it and generate a new one immediately.

## 🛠️ Development

Clone the repository:

```bash
git clone https://github.com/kprojects87/bulk-post-generator.git
```

Move the plugin into your WordPress installation:

```text
wp-content/plugins/bulk-post-generator/
```

Activate the plugin from the WordPress dashboard and begin development.

## 🔄 Updating the Plugin

To get the latest changes from GitHub:

```bash
git pull origin main
```

To upload your local changes:

```bash
git add .
git commit -m "Update plugin"
git push
```

## 🐛 Bug Reports

If you find a bug, please open an issue with:

* WordPress version
* PHP version
* Plugin version
* Steps to reproduce the issue
* Error message
* Relevant screenshots or logs

## 💡 Feature Requests

Feature suggestions and improvements are welcome.

When requesting a feature, explain:

1. What the feature should do
2. Why it is useful
3. How you expect it to work

## 🔮 Roadmap

Possible future improvements:

* [ ] Advanced bulk generation controls
* [ ] More AI model integrations
* [ ] Improved image generation
* [ ] Scheduled content generation
* [ ] SEO metadata generation
* [ ] Keyword-based content generation
* [ ] Content templates
* [ ] Post-generation history
* [ ] Generation logs
* [ ] Additional image providers
* [ ] WooCommerce product content generation

## 🤝 Contributing

Contributions are welcome.

1. Fork the repository.
2. Create a new branch.
3. Make your changes.
4. Test the plugin.
5. Commit your changes.
6. Submit a pull request.

## 📄 License

This project is currently provided for development and educational purposes.

License information can be updated when the project is released publicly.

## 👨‍💻 Author

**Khizer Qureshi**

WordPress Developer

GitHub:
https://github.com/kprojects87

---

⭐ If you find this project useful, consider starring the repository.
