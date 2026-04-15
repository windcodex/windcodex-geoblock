=== WindCodex GeoBlock ===
Contributors: windcodex
Tags: woocommerce, country restriction, geolocation, geo blocking, product visibility
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 10.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restrict WooCommerce products by country. Hide products, block purchases, or show a custom message using geolocation - no API key required.

== Description ==

**Country Restrictions for WooCommerce** lets you control exactly which products customers can see and buy based on their country - using WooCommerce's built-in geolocation engine. No API key. No third-party service. No code required.

Whether you need to comply with regional laws, manage export restrictions, honour licensing agreements, or limit shipping destinations - this plugin gives you precise per-product country control in minutes.

= Why store owners choose this plugin =

* **No more failed deliveries** - Stop customers from ordering products you can't ship to their country.
* **Stay legally compliant** - Block products restricted by local law (alcohol, supplements, age-restricted items, electronics).
* **Honour licensing agreements** - Restrict digital or licensed products to territories your agreement covers.
* **Create country-specific catalogs** - Show a tailored product selection to each market, automatically.
* **Reduce chargebacks** - Prevent orders you'd only have to cancel and refund.

= 3 Restriction Modes - Choose how to handle each restricted product =

**Hide completely**
Product is removed from shop pages, search results, and category listings for restricted visitors. Direct product URL returns a 404 page. Optionally redirect restricted visitors to a custom URL instead.

**Hide from catalog, allow direct URL**
Product is hidden from shop and search. The product page still loads via direct link - useful for distributors or partners in restricted regions. You can optionally allow full purchase via direct URL with a single toggle.

**Show restriction message**
Product stays visible in the catalog. On the product page, the Add to Cart button and price are replaced with your custom HTML message. Perfect for explaining regional availability without making the product invisible.

= Key Features =

* **Per-product country rules** - Set include or exclude rules on any product from the product edit screen. Searchable country selector with live rule summary.
* **Include or Exclude mode** - Whitelist specific countries (only they can buy) or blacklist countries (everyone except them can buy).
* **Custom restriction message** - Write your own HTML message. Supports links, bold text, and any markup allowed by WordPress.
* **Configurable message position** - Place the restriction notice above the title, below the title, after the description, or before product meta.
* **Redirect on restriction** - In Hide mode, redirect restricted visitors to any URL instead of a 404 page.
* **Cart & checkout protection** - Restricted products added to cart before a rule was applied are automatically removed at cart and checkout, with a clear notice.
* **Server-side add-to-cart validation** - Blocks direct POST, REST API, and AJAX add-to-cart attempts for restricted products. No client-side-only protection.
* **Smart country detection (6 layers)** - Shipping address -> billing address -> WC session -> transient cache -> MaxMind GeoLite2 -> store base. No manual input needed from shoppers.
* **Cloudflare IP support** - Reads `CF-Connecting-IP` automatically for sites behind Cloudflare.
* **Force Geolocation option** - Ignore saved addresses and always detect country from IP.
* **Session & transient caching** - Country detection results are cached per session for performance. Zero repeated geolocation lookups.
* **Debug toolbar** - Admin-only bar at the bottom of every frontend page showing detected country, IP address, detection source, and a direct link to settings.
* **Shortcode support** - `[geoblock_product_message]` renders the restriction message anywhere - page builders, custom templates, Elementor, Divi.
* **HPOS compatible** - Fully compatible with WooCommerce High-Performance Order Storage.
* **Translation ready** - Complete `.pot` file included. Fully translatable via Loco Translate or WPML String Translation.
= Plugin Compatibility =

Built-in compatibility layers for 6 popular plugins - no configuration needed:

* **Price Based on Country for WooCommerce** - Restriction message takes priority over country-based pricing on restricted products.
* **WPML** - Rules set on the original-language product automatically apply to all translated copies. No duplicate rules per language.
* **WooCommerce Product Bundles** - Restriction on the bundle parent blocks the entire bundle from being purchased.
* **WooCommerce Subscriptions** - Both `subscription` and `variable-subscription` product types are fully supported.
* **WP Rocket** - Product pages automatically excluded from page cache so every visitor gets the correct restricted/unrestricted version.
* **Speed Optimizer by SiteGround** - Product pages bypass SiteGround's dynamic cache using `SGCACHENOCACHE` and `sgo_bypass_cache`.

= Use Cases =

* Block alcohol or age-restricted products from countries where they're prohibited
* Restrict physical products to countries you can ship to
* Limit digital downloads to licensed territories
* Hide out-of-stock regional variants from other markets
* Show a -contact us to order in your region- message instead of hiding the product
* Create exclusive country-specific product catalogs

= Shortcode =

Display the restriction message anywhere using:
`[geoblock_product_message]`

**Attributes:**
* `id` - Product ID to check. Defaults to current product in the loop.
* `class` - Extra CSS class(es) added to the message wrapper div.

**Examples:**
`[geoblock_product_message id=123]`
`[geoblock_product_message id=123- class=my-notice highlight]`

= How It Works =

1. Install and activate the plugin.
2. Go to **WooCommerce > GeoBlock Restrictions** and choose a restriction mode.
3. Open any product and find the **GeoBlock - Country Restrictions** meta box.
4. Select Include or Exclude, choose your countries, and save.
5. GeoBlock automatically detects each visitor's country and applies your rules in real time.

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 7.0 or higher
* PHP 7.4 or higher
* WooCommerce Geolocation: **WooCommerce Settings > General > Default customer location > Geolocate**

= Privacy =

GeoBlock uses the visitor's IP address solely to determine their country. No personal data is stored permanently. Detection results are cached in WooCommerce session storage and WordPress transients with a 1-hour TTL. No data is sent to external servers. This plugin does not collect, sell, or share any visitor data.

== Installation ==

**From your WordPress dashboard:**

1. Go to **Plugins Add New**.
2. Search for **WindCodex GeoBlock**.
3. Click **Install Now**, then **Activate**.

**Manual installation:**

1. Download the plugin ZIP file.
2. Upload the `windcodex-geoblock` folder to `/wp-content/plugins/`.
3. Activate through the **Plugins** screen in WordPress.

**After activation:**

1. Go to **WooCommerce Settings > General** and set -Default customer location- to **Geolocate** or **Geolocate (with page caching support)**.
2. Go to **WooCommerce > GeoBlock Restrictions** to choose your global restriction mode.
3. Edit any product and use the **GeoBlock - Country Restrictions** meta box to set rules.

== Frequently Asked Questions ==

= Does this plugin require any API key or account? =

No. This plugin uses WooCommerce's built-in geolocation engine (MaxMind GeoLite2), which is already included with WooCommerce. No external API key, account, or paid service is required.

= Which countries can I restrict? =

All countries supported by WooCommerce - the same full country list used in WooCommerce shipping and tax settings. You can restrict any single country or any combination of countries on each product.

= Will this work with page caching plugins like WP Rocket or LiteSpeed? =

Yes. Set WooCommerce's -Default customer location- to **Geolocate (with page caching support)** (AJAX mode). Country detection happens via AJAX after page load, so it works correctly even on fully cached pages. GeoBlock also includes a built-in WP Rocket compatibility layer that excludes product pages from cache.

= What happens if geolocation is not enabled in WooCommerce? =

GeoBlock shows a warning banner in the plugin settings page. Without geolocation, guest visitors fall back to the store's base country and restrictions will not apply to them. Logged-in customers with a saved shipping or billing address are still detected correctly.

= Does this work with variable products and product variations? =

Yes. Country rules are set at the parent product level and automatically apply to all variations. In Hide and Catalog Only modes, the entire product including all variations is restricted. In Message mode, the variation dropdowns and Add to Cart button are both hidden on the product page.

= What if a customer uses a VPN? =

GeoBlock detects country based on IP address. A customer using a VPN may appear to be in a different country. For stores where VPN circumvention is a concern, we recommend enabling the -Force Geolocation- option which ignores saved addresses and always uses current IP detection.

= What happens if a restricted product is already in a customer's cart? =

GeoBlock automatically removes restricted products from the cart when the customer visits the cart or checkout page, and displays a clear notice explaining why. Checkout is also blocked as a final safety net even if the cart page was skipped.

= Can I set different rules for different products? =

Yes. Each product has its own independent set of rules. You can have Product A restricted to only Europe, Product B excluded from one country, and Product C with no restrictions at all - all at the same time.

= Does restricting products affect SEO or Google indexing? =

No. Country restrictions are applied at the application level for logged-in users and detected visitors. Search engine crawlers are not affected and your products continue to be indexed normally. Country-based restrictions are a standard and accepted practice in international e-commerce.

= Is this plugin GDPR compliant? =

Yes. GeoBlock processes IP addresses only to determine the visitor's geographic country for restriction purposes - a legitimate interest under e-commerce operation. No personal data is stored beyond the session/transient TTL (1 hour). No data is shared with third parties. Review and update your own privacy policy to document your use of geolocation.

= Does this work with WooCommerce Subscriptions? =

Yes. GeoBlock's compatibility layer hooks into WooCommerce Subscriptions' own purchasability filters, fully supporting both `subscription` and `variable-subscription` product types.

= Can I use this with Elementor, Divi, or other page builders? =

Yes. Use the `[geoblock_product_message id=PRODUCT_ID]` shortcode in any page builder element or custom template to display the restriction message anywhere on your site.

= How do I test that restrictions are working correctly? =

Enable Debug Mode in **WooCommerce GeoBlock Restrictions Advanced**. A debug toolbar will appear at the bottom of every frontend page (visible to admins only) showing the detected country, IP address, and detection method. You can also temporarily change the restriction rule to include your own country to verify the hide/message behaviour.

= Will restricted products still be accessible via direct URL in -Hide completely- mode? =

In Hide completely mode, the direct product URL returns a 404 page. You can optionally configure a redirect URL to send restricted visitors to a custom page (like a contact or coming-soon page) instead of a 404.

== Screenshots ==

1. **Settings Page - General Tab** - Choose restriction mode (Hide, Catalog Only, or Message), configure redirect URL, and write your custom restriction message.
2. **Settings Page - Advanced Tab** - Force Geolocation and Debug Mode toggles.
3. **Product Meta Box** - Per-product Include/Exclude country rule with searchable country selector and live rule summary preview.

== Changelog ==

= 1.0.0 =
* Initial release.
* Three restriction modes: Hide completely, Hide from catalog (Catalog Only), Show restriction message.
* Per-product Include/Exclude country rules with searchable Select2 country selector.
* Custom restriction message with HTML support and configurable position on product page (4 positions).
* Redirect URL option for Hide completely mode.
* Allow purchase via direct URL toggle for Catalog Only mode.
* 6-layer country detection: shipping address -> billing address -> WC session -> transient cache -> MaxMind GeoLite2 -> store base.
* Cloudflare `CF-Connecting-IP` header support for reverse-proxied sites.
* Session and transient caching for geolocation performance (1-hour TTL).
* Force Geolocation option to ignore saved addresses and always use IP detection.
* Cart and checkout protection - restricted products added before rule was applied are auto-removed.
* Server-side add-to-cart validation via `woocommerce_add_to_cart_validation` blocks API/AJAX bypass attempts.
* Variation ID parent resolution - rules on parent product correctly block all child variations.
* `[geoblock_product_message]` shortcode for page builder and custom template compatibility.
* Admin debug toolbar (admin-only) showing country, IP, and detection source.
* AJAX save and reset with toast notifications and sticky footer save bar.
* HPOS (High-Performance Order Storage) compatibility declared.
* WooCommerce Blocks compatibility.
* Built-in compatibility: Price Based on Country, WPML, WooCommerce Product Bundles, WooCommerce Subscriptions, WP Rocket, Speed Optimizer by SiteGround.
* Translation-ready with complete `.pot` file included.

== Upgrade Notice ==

= 1.0.0 =
Initial release - no upgrade steps required.
