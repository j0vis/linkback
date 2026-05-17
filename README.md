# 🔗 LinkBack — WordPress Partner Directory & Link Exchange Manager

[![WordPress Version](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg?style=flat-square&logo=wordpress)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-purple.svg?style=flat-square&logo=php)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-orange.svg?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.1.1-green.svg?style=flat-square)](https://github.com/j0vis/linkback)

LinkBack is a feature-rich, high-performance WordPress plugin designed to help webmasters run a premium **Partner Directory & Link Exchange Program**. Featuring automated reciprocal link verification, anti-cheat detection, grace periods, hit-based ranking, customizable glassmorphism frontend layouts, and detailed traffic analytics, LinkBack handles all link trading operations automatically.

---

## ✨ Key Features

### 🔍 Automated Verification & Anti-Cheat
* **Multiple Verification Methods**: Checks partner pages for your domain name or a user-defined custom verification string.
* **Nofollow Checker**: Parses the exact `<a>` tag of the partner link to verify that it does not contain a `rel="nofollow"` attribute.
* **Robots.txt Analysis**: Checks the partner's `robots.txt` file to ensure the link page is indexable and not blocked by a `Disallow` rule.
* **Verification Caching**: Prevents redundant network requests by caching successful verifications for a configurable duration.

### ⏳ Grace Period State Machine
* **Automated WP-Cron Monitoring**: Periodically scans active links and monitors transition statuses.
* **Grace Status**: When a partner link goes missing, the system places it in a `grace` status and logs the exact timestamp.
* **Automatic Removal**: Disabled listings automatically after the grace period expires (defaults to 7 days).
* **Restoration & Notifications**: Sends robust, templated email alerts to both the administrator and the partner upon grace entry, status changes, and link restoration.

### 📊 Click Tracking & Analytics
* **SEO-Friendly Direct URLs**: Frontend listings output clean direct links for search engine indexing.
* **Beacon-based Redirects**: Outgoing clicks are tracked using `navigator.sendBeacon` (falling back to image beacons) without disrupting the user's navigational experience.
* **Smart Referrer Validation**: Identifies and logs incoming hits (`hits_in`) on every frontend page load by verifying referrer hosts.
* **Anti-Spam Session Caching**: Prevents hit inflation using a 30-minute cookie-based duplicate filter.
* **Daily Time-Series Statistics**: Aggregates historic tracking data into daily rows (`hits_in` and `hits_out`) in a dedicated stats table.

### 📝 Premium Frontend Signup & Forms
* **Spam Safeguards**: Built-in honeypot field, client-side IP-based submission rate limits, reCAPTCHA v2 support, and strict duplicate URL/email validators.
* **Embed Code Generator**: Generates an easy-to-copy HTML reference link snippet for partners.
* **Logo Support & Fallback**: Renders custom logos, grabs favicons, or generates modern fallback gradients containing the first letter of the site name.

---

## 🛠️ Database Schema

LinkBack utilizes two custom, lightweight, indexed database tables to manage listings and historic analytics:

### 1. `{prefix}linkback_links` (Listings & Verification Statuses)
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `BIGINT` | Primary Key (Auto-Increment) |
| `site_name` | `VARCHAR(255)` | Name of the partner's website |
| `site_url` | `VARCHAR(255)` | Destination URL of the partner's website |
| `backlink_url` | `VARCHAR(255)` | Partner's page containing your backlink |
| `email` | `VARCHAR(100)` | Contact email of the partner |
| `description` | `TEXT` | Optional site description |
| `logo_url` | `VARCHAR(255)` | Direct link to a custom logo image |
| `twitter_handle`| `VARCHAR(50)` | Twitter / X handle of the partner |
| `anchor_text` | `VARCHAR(100)` | Preferred anchor text for the link |
| `category` | `VARCHAR(100)` | Directory category designation |
| `hits_in` | `INT` | Total verified incoming hits |
| `hits_out` | `INT` | Total outgoing clicks tracked |
| `reciprocal_required` | `TINYINT(1)` | Whether this listing requires a reciprocal link (default: `1`) |
| `reciprocal_status` | `VARCHAR(20)` | `ok`, `grace`, `removed`, or `pending` |
| `reciprocal_last_checked` | `DATETIME` | Timestamp of the last cron verification check |
| `reciprocal_fail_since` | `DATETIME` | Timestamp of when the backlink first failed checks |
| `payment_status` | `VARCHAR(20)` | Placements status: `free`, `pending`, or `paid` |
| `payment_amount` | `DECIMAL(10,2)` | Optional placement fee paid |
| `is_featured` | `TINYINT(1)` | Featured status flag for top-priority sorting (default: `0`) |
| `is_dead` | `TINYINT(1)` | Flag for repeatedly unreachable hosts (default: `0`) |
| `dead_fail_count` | `INT` | Consecutive failed network attempts |
| `last_dead_check` | `DATETIME` | Timestamp of the last dead-link verification |
| `display_order` | `INT` | Manual order positioning override |
| `is_active` | `TINYINT(1)` | Frontend visibility flag (default: `1` for manually added, `0` for signup queue) |
| `created_at` | `DATETIME` | Creation timestamp |
| `updated_at` | `DATETIME` | Modification timestamp |

### 2. `{prefix}linkback_stats` (Time-Series Analytics)
This table stores aggregated daily click volumes:
* `link_id` (`BIGINT`): Foreign key referencing `{prefix}linkback_links`.
* `stat_date` (`DATE`): The calendar date of the recorded statistics.
* `hits_in` (`INT`): Incoming hits for this day.
* `hits_out` (`INT`): Outgoing hits for this day.

---

## 🚀 Installation & Quick Start

### Manual Installation
1. Download this repository as a `.zip` file or clone it directly.
2. Extract the archive and copy the `linkback` folder to your `/wp-content/plugins/` directory:
   ```bash
   git clone https://github.com/j0vis/linkback.git wp-content/plugins/linkback
   ```
3. Navigate to the **Plugins** page in your WordPress Admin dashboard and activate **LinkBack**.

> [!NOTE]
> Upon activation, LinkBack automatically runs the `dbDelta()` engine to create the custom database tables and schedules the required cron events.

---

## 🎨 Layouts & Shortcodes

LinkBack provides two responsive frontend shortcodes. They automatically inherit your theme's fonts and colors, with optional light/dark styles, sleek glassmorphism panels, and elegant hover lifts:

### 1. Directory Listings (`[linkback]`)
Displays your partner directory cards.
* **Syntax**: `[linkback count="12" category="technology" order="hits_in" direction="desc"]`
* **Attributes**:
  * `count` (int): Maximum number of listings to display (defaults to the plugin settings value).
  * `category` (string): Filter links by category name (e.g. `technology`).
  * `order` (string): Order listings by `hits_in`, `hits_out`, `site_name`, or `created_at`.
  * `direction` (string): Sorting order direction: `desc` or `asc`.

### 2. Partner Signup Queue (`[linkback_signup]`)
Displays a public partner application form.
* **Syntax**: `[linkback_signup]`
* **Features**:
  * Automatic reciprocal code block generator showing the exact HTML code they need to copy to link back to your website.
  * Multi-field options including Site Name, Site URL, Partner Link URL, Email, Description, Logo URL, Anchor Text, Category, and Twitter Handle.
  * Honeypot field and CAPTCHA checking to shield your directory from spam.

---

## 💻 Headless / REST API Endpoints

LinkBack registers custom WordPress REST API paths under the `/wp-json/linkback/v1` namespace for headless React, Next.js, or mobile integrations:

* **`GET /wp-json/linkback/v1/links`**: Returns a list of active public directory listings.
  * *Parameters*: `count` (int), `category` (string), `order` (string), `direction` (string).
* **`POST /wp-json/linkback/v1/signup`**: Submits a new directory listing application.
  * *Body*: JSON containing `site_name`, `site_url`, `backlink_url`, `email`, `description`, `logo_url`, `anchor_text`, `category`, and spam protection tokens.
* **`GET /wp-json/linkback/v1/track/{id}`**: Registers an outgoing click and returns a redirection confirm payload.

---

## 🎨 Theme Customization & Template Overrides

LinkBack supports standard WordPress template overrides. This allows you to redesign frontend directory layouts inside your theme files without losing modifications during plugin updates:

1. Create a folder named `linkback` inside your active theme directory:
   `wp-content/themes/your-theme/linkback/`
2. Copy the template files from the plugin directory into your theme folder:
   * **Listing Layout**: Copy `templates/list.php` to `your-theme/linkback/list.php`
   * **Signup Form**: Copy `templates/signup-form.php` to `your-theme/linkback/signup-form.php`
3. Edit the copied templates as desired. LinkBack will automatically load your theme versions instead of its defaults!

---

## 🛡️ Security & Standards

* **Capability Safeguards**: Admin operations require the standard `manage_options` capability.
* **Data Sanitization**: All incoming data is rigorously sanitized via `sanitize_text_field`, `esc_url_raw`, `sanitize_email`, `absint`, or custom validation filters prior to database insertion.
* **Output Escaping**: Every single public or administrative rendering path is secured with native WordPress output escaping (`esc_html`, `esc_attr`, `esc_url`, `esc_textarea`).
* **CSRF Protection**: Comprehensive WordPress nonce validation safeguards all actions, including setting saves, link status transitions, and bulk updates.
* **Safe Redirects**: Click redirections are verified and routed securely using `wp_safe_redirect`.

---

## 📝 License

This project is licensed under the GPL-2.0-or-later License. Feel free to copy, modify, and redistribute this software as per the GNU General Public License terms.
