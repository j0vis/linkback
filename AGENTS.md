# LinkBack — Agent Guide

## Project Overview

LinkBack is a WordPress plugin that implements a partner directory program with partner link verification, hit-based ranking, anti-cheat detection, payment tracking, and a frontend signup form. The plugin is written in PHP and follows classic WordPress plugin conventions. There is no build step or dependency manager — files are edited directly.

- **Plugin Name:** LinkBack
- **Version:** 1.1.0
- **Requires WordPress:** 6.0+
- **Requires PHP:** 8.0+
- **License:** GPL-2.0-or-later
- **Text Domain:** `linkback`

## Project Structure

```
linkback/
├── linkback.php                 # Main plugin file — bootstrap, activation/deactivation, global hooks, REST API, template loader
├── readme.txt                   # WordPress.org-style readme
├── admin/
│   ├── class-admin.php          # Registers admin menus, enqueues admin assets, dashboard widget, AJAX verify
│   ├── class-admin-controller.php # Handles POST/GET actions (save, delete, bulk, verify, approve, reject, settings, import, export)
│   └── views/
│       ├── add-edit-link.php    # Add / Edit link form (includes new fields: logo, twitter, anchor text, category, featured)
│       ├── links-list.php       # Admin list table with bulk actions, filters, search, AJAX verify, dead status
│       ├── settings.php         # Plugin settings page (spam protection, email templates, dark mode, caching, dead threshold)
│       └── import-export.php    # CSV import/export tools
├── includes/
│   ├── class-database.php       # Creates and references custom DB tables (links + stats)
│   ├── class-link.php           # Link data model (CRUD, rendering, hit tracking, stats, categories, deduplication)
│   ├── class-validator.php      # Shared validation logic (signup form, REST API)
│   ├── class-mailer.php         # Email notification builder and sender
│   ├── class-verification-state.php # State-machine transitions for link verification results
│   ├── class-csv-handler.php    # CSV import/export engine
│   ├── class-cron.php           # WP-Cron scheduling/unscheduling (uses static callables, no global wrappers)
│   ├── class-reciprocal-checker.php # Remote partner link verification, anti-cheat, dead detection, grace processing
│   └── class-rest-api.php       # REST API routes: GET /links, POST /signup, GET /track/{id}
├── public/
│   ├── class-shortcode.php      # `[linkback]` and `[linkback_signup]` shortcodes with spam protection and dark mode body class
│   └── class-widget.php         # `LinkBack_Widget` widget with category filter and random order
├── templates/
│   ├── list.php                 # Default template for frontend listings (theme override supported)
│   └── signup-form.php          # Default template for frontend signup form (theme override supported)
└── assets/
    ├── css/
    │   ├── admin.css            # Status badges, admin table styling, spinner animation
    │   └── public.css           # Frontend list styling, glassmorphism cards, dark mode, signup form, featured badges
    └── js/
        └── admin.js             # "Select all" checkbox helper + AJAX inline verify
```

## Technology Stack

- **Backend:** PHP 8.0+, WordPress APIs (`$wpdb`, `WP_Widget`, `wp_remote_get`, `wp_mail`, `register_rest_route`, etc.)
- **Frontend:** Vanilla CSS with glassmorphism design, dark mode support (`prefers-color-scheme` + force classes), vanilla JavaScript (jQuery in admin)
- **Database:** WordPress `dbDelta()` custom tables (`{prefix}linkback_links`, `{prefix}linkback_stats`)
- **No build tools:** There is no `package.json`, `composer.json`, `webpack`, or CI configuration. Changes are made directly to source files.

## Activation & Deactivation

- **Activation** (`linkback_activate` in `linkback.php`):
  - Creates the custom tables via `LinkBack_Database::create_tables()`.
  - Schedules WP-Cron events via `LinkBack_Cron::schedule()`.
  - Sets default options (check frequency, grace period, default partner link, max display, verification method/string, notification toggles, admin email, default title, signup link toggle, spam protection, dark mode, caching, dead threshold, email templates, payment settings).
  - Calls `flush_rewrite_rules()`.

- **Deactivation** (`linkback_deactivate` in `linkback.php`):
  - Unschedules WP-Cron events via `LinkBack_Cron::unschedule()`.
  - Calls `flush_rewrite_rules()`.

- **Database Upgrades:**
  - `linkback_check_upgrade()` runs on `plugins_loaded` and compares `linkback_db_version` against `LINKBACK_VERSION`.
  - If the stored version is older, `dbDelta()` re-runs automatically without requiring manual deactivation/reactivation.

## Data Model

The custom table `{prefix}linkback_links` stores:

| Column | Purpose |
|--------|---------|
| `site_name` / `site_url` | Partner site info |
| `backlink_url` | URL on partner site expected to contain a reference link |
| `email` | Partner contact email |
| `description` | Optional text description |
| `logo_url` | Custom logo/favicon image URL |
| `twitter_handle` | Social handle |
| `anchor_text` | Preferred anchor text |
| `category` | Listing category |
| `hits_in` / `hits_out` | Click tracking |
| `reciprocal_required` | Whether this listing must have a partner reference link (`0` or `1`) |
| `reciprocal_status` | `ok`, `grace`, `removed`, or `pending` |
| `reciprocal_last_checked` | Timestamp of the most recent verification check |
| `reciprocal_fail_since` | When the partner link was first detected missing |
| `payment_status` | `free`, `pending`, or `paid` |
| `payment_amount` | Decimal amount |
| `is_featured` | Featured / sponsored flag (`0` or `1`) |
| `is_dead` | Repeatedly unreachable (`0` or `1`) |
| `dead_fail_count` | Consecutive failed reachability checks |
| `last_dead_check` | Timestamp of last dead-link check |
| `display_order` | Manual ordering |
| `is_active` | Whether the link is visible on the frontend (`0` or `1`) |
| `created_at` / `updated_at` | Automatic timestamps |

Stats table `{prefix}linkback_stats` stores daily time-series data (`link_id`, `stat_date`, `hits_in`, `hits_out`).

All DB queries are performed through `$wpdb` with prepared statements where appropriate. `LinkBack_Link` is a static utility class — not a true ORM — that encapsulates CRUD and query building.

## Key Features & Logic

### Partner Link Verification
- `LinkBack_Reciprocal_Checker::check_all()` is triggered by WP-Cron (`linkback_check_reciprocal`).
- It fetches all active listings where `reciprocal_required = 1` and performs an HTTP `GET` to `backlink_url`.
- **Verification methods:**
  - **Domain:** Checks if the page body contains the site's domain name.
  - **Custom String:** Checks for a user-defined verification string.
- **Anti-cheat:**
  - Detects `rel="nofollow"` on the partner link anchor via regex matching the `<a>` tag containing the domain.
  - Checks `robots.txt` for a `Disallow` rule on the partner page path.
- **Verification caching:** If a link is `ok`, re-checks are skipped until `verification_cache_hours` has elapsed.
- **Dead-link detection:** If the page returns `>= 400` or is unreachable, a `dead_fail_count` is incremented. After `dead_threshold_checks` consecutive failures, `is_dead` is set to `1`.
- **State transitions:** `LinkBack_Verification_State::apply()` encapsulates all status transitions (found → ok, missing → grace, unreachable → dead counter, restoration notifications).

### Grace Period
- When a partner link is first found missing, the listing enters `grace` status and `reciprocal_fail_since` is set.
- A separate daily cron (`linkback_process_grace`) disables links whose grace period has exceeded the configured number of days (default 7).
- Notifications are sent to the admin (and partner, if an email is stored) on grace entry, removal, **and restoration** when the link is fixed before removal.

### Click Tracking
- **Outgoing:** Frontend links in `templates/list.php` are direct SEO-friendly URLs. A JavaScript beacon (`navigator.sendBeacon` or image fallback) fires on click to `?linkback_redirect={id}&_wpnonce=...`. The `linkback_handle_redirect()` handler in `linkback.php` returns `204 No Content` for beacon requests and only falls back to a `302` redirect for direct visits or bookmarks.
- **Incoming:** On every frontend page load (`wp` hook), the plugin checks `HTTP_REFERER` and increments `hits_in` for any listing whose `site_url` or `backlink_url` host matches the referrer host. A 30-minute session cookie prevents duplicate counting.

### Frontend Signup
- The `[linkback_signup]` shortcode renders a public submission form via `templates/signup-form.php` (theme-overridable). Submissions create a new listing with `is_active = 0` and `reciprocal_status = 'pending'`.
- The form includes a "Copy Code" box that generates a reference link snippet for the current site.
- **Spam protection:** Honeypot field, IP-based rate limiting, reCAPTCHA v2 support, and duplicate URL/email guard. Validation is centralized in `LinkBack_Validator::validate_signup()` and shared between the shortcode and REST API.
- If `linkback_enable_signup_link` is enabled, `LinkBack_Link::render_list()` appends a "Submit Your Site" button.
- New fields: logo URL, Twitter/X handle, preferred anchor text, category.

### Frontend Rendering
- `LinkBack_Link::render_list()` delegates to `templates/list.php` via `linkback_get_template()`.
- **Template overrides:** Themes can override `templates/list.php` or `templates/signup-form.php` by placing files in `yourtheme/linkback/`.
- Each card attempts to show a screenshot via WordPress.com mshots (`https://s.wordpress.com/mshots/v1/...`) or a custom `logo_url`.
- If the image fails to load, a gradient avatar with the first letter of the site name is shown as a fallback.
- Featured listings display a gold badge and are sorted above non-featured if `featured_priority` is enabled.
- The public stylesheet supports light/dark modes (auto via `prefers-color-scheme`, or forced via admin setting), glassmorphism, hover lift effects, and responsive grids.

### REST API
- `GET /wp-json/linkback/v1/links` — public, returns active listings with optional `count`, `order`, `direction`, `category`.
- `POST /wp-json/linkback/v1/signup` — public JSON signup with duplicate guard.
- `GET /wp-json/linkback/v1/track/{id}` — returns JSON hit confirmation and target URL (useful for AJAX tracking).

### Admin Pages

1. **LinkBack → All Links** (`admin.php?page=linkback`)
   - Lists all links with status filters (All, Active, Grace Period, Pending Queue, Dead).
   - Supports inline search by site name, URL, or email.
   - Bulk actions: Approve, Reject, Activate, Deactivate, Verify Selected, Delete.
   - Inline actions: Edit, Verify Now, AJAX Verify, Delete. Pending links show Allow/Deny buttons instead.

2. **LinkBack → Add New** (`admin.php?page=linkback-add`)
   - Form to create or edit a link.
   - Fields: site name, site URL, partner page URL, email, description, logo URL, Twitter handle, anchor text, category, partner link toggle, payment status/amount, featured toggle, display order, active toggle.

3. **LinkBack → Settings** (`admin.php?page=linkback-settings`)
   - Default directory title, submit-your-site button toggle, check frequency, grace period, default partner link, max display, verification method/string, notification toggles, admin email, spam protection settings, dark mode, verification cache, dead threshold, featured priority, email templates, payment settings.
   - Saving settings triggers `LinkBack_Cron::reschedule()`.

4. **LinkBack → Import / Export** (`admin.php?page=linkback-import-export`)
   - Export all listings to CSV via `LinkBack_CSV_Handler::export()`.
   - Import listings from CSV with header mapping (`site_name`, `site_url`, `backlink_url`, `email`, `description`, `category`) via `LinkBack_CSV_Handler::import()`.

### Dashboard Widget
- A WordPress Dashboard widget shows:
  - Total, Active, Grace, Pending, Dead counts.
  - Top 5 outgoing clicks.
  - Quick link to the admin listings page.

## Code Conventions

- **Namespace:** `LinkBack_*` for all classes.
- **Constants:** `LINKBACK_*` for version, paths, and table slug.
- **File guard:** Every PHP file starts with `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
- **Sanitization/escaping:**
  - Use `sanitize_text_field`, `esc_url_raw`, `sanitize_email`, `absint`, `floatval` on input.
  - Use `esc_html`, `esc_attr`, `esc_url`, `esc_textarea` on output.
  - Nonces (`wp_nonce_field` / `check_admin_referer`) protect all mutating actions.
- **i18n:** All user-facing strings use `__()`, `_e()`, `esc_html_e()`, or `esc_html__()` with the `linkback` text domain.
- **No PSR-4 autoloading:** Classes are manually `require_once`-d from `linkback.php` in dependency order.
- **Refactoring discipline:** Duplicated logic is extracted into shared static methods (`LinkBack_Validator`, `LinkBack_Mailer`, `LinkBack_CSV_Handler`, `LinkBack_Verification_State`, `LinkBack_Link::build_where()`, `LinkBack_Link::get_public_links()`). Admin action consolidation uses private helpers (`do_approve`, `do_verify`). Settings persistence uses a declarative whitelist array. Templates receive variables via an explicit `$args` array (no `extract()`).

## Build and Test Commands

There is no build process. The plugin runs directly as PHP source.

- **No package manager** (no `npm`, `composer`, `yarn`).
- **No bundler** (no `webpack`, `vite`, `rollup`).
- **No linter or formatter** configured.
- **No CI/CD** configuration files.

To verify changes, install the plugin in a WordPress environment and test manually (see Testing Instructions below).

## Testing Instructions

There are currently no automated tests (no PHPUnit, no JS test runner). Verification is done manually by:

1. Installing the plugin in a WordPress environment.
2. Activating it and confirming the custom tables are created.
3. Adding test links and checking the list table, shortcode, and widget output.
4. Submitting a test request via the `[linkback_signup]` shortcode and verifying it appears in the Pending Queue.
5. Triggering the cron hooks (e.g., with WP Crontrol or `wp cron event run`) to verify partner link checking and grace-period logic.
6. Testing incoming hit tracking by simulating referers and observing `hits_in` increment.
7. Testing REST API endpoints via browser or Postman.
8. Testing CSV import/export with sample files.

## Security Considerations

- All admin actions require `manage_options` capability (via `add_menu_page` / `add_submenu_page`).
- Nonce verification is enforced on every state-changing request (`save_link`, `delete_link`, `bulk_action`, `verify_link`, `approve_link`, `reject_link`, `save_settings`, `import_csv`, `export_csv`).
- The frontend signup form uses `wp_nonce_field` with `wp_verify_nonce`.
- SQL queries use `$wpdb->prepare()` or `sanitize_sql_orderby()`.
- Redirects use `wp_safe_redirect` / `wp_redirect` with `esc_url_raw()`.
- Remote requests for verification use `wp_remote_get` with a custom user-agent (`LinkBack Checker/{version}`) and `sslverify` disabled — be cautious if running in high-security environments.
- Spam protection includes honeypot fields, IP rate limiting, reCAPTCHA v2, and duplicate submission guards.

## How to Make Changes

1. Edit the relevant file directly (no build step required).
2. If adding a new class, `require_once` it in `linkback.php`.
3. If adding a new admin action, add the nonce check and handler method in `class-admin-controller.php`, then wire it into `LinkBack_Admin_Controller::handle_actions()`.
4. If adding a new option, register its default in `linkback_activate()` and add it to the `$settings` whitelist in `class-admin-controller.php::save_settings()`.
5. If adding a new frontend template, place it in `templates/` and reference it via `linkback_get_template()`.
6. Bumping the version requires updating the `Version:` header in `linkback.php`, the `LINKBACK_VERSION` constant, and `readme.txt`.
