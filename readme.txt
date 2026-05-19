=== LinkBack ===
Contributors: prof
Tags: links, partner, directory, seo, resource
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.1.3
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A partner directory plugin for WordPress with link verification, hit ranking, anti-cheat, and payment support.

== Description ==

LinkBack allows webmasters to manage a partner directory directly from WordPress.

**Features:**
* Partner link verification with anti-cheat detection
* Automatic 7-day grace period before removing broken partner links
* Hit-based ranking (incoming and outgoing clicks tracked)
* Admin can mark listings as not requiring a partner link
* Widget and shortcode support for flexible placement
* Payment status tracking for paid link placements
* Email notifications for missing or removed links
* Custom verification string or domain-based checking
* Featured / sponsored listings with priority sorting
* Category filtering for listings
* Historical time-series stats tracking
* Spam protection (honeypot, rate limiting, reCAPTCHA)
* REST API endpoints for headless frontends
* CSV import / export
* Template overrides via theme
* Dead link auto-detection
* AJAX inline verification in admin

== Installation ==

1. Upload the `linkback` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **LinkBack > Settings** to configure your preferences
4. Add partner links under **LinkBack > Add New**
5. Use the widget or `[linkback]` shortcode to display links

== Frequently Asked Questions ==

= How does the partner link verification work? =

The plugin checks the partner's page for your domain name (or a custom verification string). If the link is missing, the partner enters a grace period before automatic removal.

= Can I disable partner link requirements for some listings? =

Yes. When adding or editing a listing, uncheck "Partner Link Required." This is useful for paid or special partner listings.

= How do I display links on my site? =

Use the **LinkBack Widget** in Appearance > Widgets, or the `[linkback]` shortcode in any post or page.

= What anti-cheat measures are included? =

The plugin detects nofollow attributes and checks if the partner page is blocked by robots.txt.

== Changelog ==

= 1.1.3 =
* Added Adult Site Mode configuration toggle to settings.
* Implemented a comprehensive, multi-tiered taxonomy of adult categories and subcategories (supporting Couples, Solo, Milfs, Trans, Gays, Hentai, Fetish/BDSM, Softcore, and more).
* Integrated custom hierarchical category option selectors (`<optgroup>`) dynamically in frontend signup forms and dashboard widget selectors.
* Seamlessly flattened category fields for easy datalist administration and searching.

= 1.1.2 =
* Added support for custom Signup Page URL in settings.
* Implemented automatic default signup page generation on plugin activation and settings save.
* Enhanced get_signup_url to dynamically resolve and fall back securely.

= 1.1.1 =
* Internal bug fixes and stability improvements.

= 1.1.0 =
* Added featured listing badges and priority sorting
* Added category support (shortcode, widget, admin filters)
* Added time-series stats table and period-based stats display
* Added spam protection: honeypot, IP rate limiting, reCAPTCHA v2
* Added REST API endpoints (/links, /signup, /track)
* Added CSV import/export tools
* Added theme template overrides (yourtheme/linkback/list.php)
* Added dead link auto-detection with configurable threshold
* Added AJAX inline verification in admin list table
* Added bulk "Verify Selected" action
* Added verification caching to reduce remote requests
* Added restore notification emails when partners fix links
* Added session-based incoming hit deduplication
* Added dark mode force toggle (auto/light/dark)
* Added customizable email templates with placeholders
* Added new signup fields: logo URL, Twitter handle, anchor text, category
* Fixed frontend cards to route through click-tracking redirect endpoint
* Added admin dashboard health widget

= 1.0.0 =
* Initial release
