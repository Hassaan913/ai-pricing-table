=== AI Pricing Table ===
Contributors: hassaan
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shortcode-first pricing table builder for WordPress with AI-generated and manual table modes.

== Description ==

AI Pricing Table lets you create pricing tables in wp-admin and embed them with a shortcode.

Version 1 is shortcode-only.

Main features:

* AI-generated pricing tables using Gemini or local Ollama
* Manual pricing-table builder with plans, features, and comparison matrix
* Import/export for saved pricing tables
* Hidden custom post type storage instead of the default post editor

== Installation ==

1. Upload the plugin to `/wp-content/plugins/ai-pricing-table/`.
2. Activate the plugin in WordPress.
3. Open `AI Pricing` in wp-admin.
4. Create a table and save it.
5. Copy the generated shortcode into a page, post, or widget.

== Usage ==

Use a specific table:

`[ai_pricing_table id="123"]`

If no `id` is supplied, the plugin attempts to render the latest saved pricing table.

== Changelog ==

= 1.0.0 =

* Initial shortcode-first release
