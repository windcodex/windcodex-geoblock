=== WindCodex GeoBlock – WooCommerce Country Restriction & Geo Blocking ===
Contributors: windcodex
Tags: woocommerce, country restriction, geo blocking, woocommerce country restrictions, restrict products by country
Requires at least: 6.9
Tested up to: 7.1
Stable tag: 1.1.0
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 11.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restrict WooCommerce products by country: hide them, block purchase or sell only to chosen countries. Uses WooCommerce geolocation. No paid API.

== Description ==

**WindCodex GeoBlock** lets you restrict WooCommerce products by country using WooCommerce's built-in geolocation – no paid API, no third-party service, no code required.

Whether you need to comply with local laws, honour export restrictions, manage licensing territories, or simply limit shipping to countries you serve – GeoBlock gives you per-product country control in minutes.

= Why Store Owners Choose GeoBlock =

* **Stop failed deliveries** – Block customers from ordering products you cannot ship to their location.
* **Stay legally compliant** – Restrict alcohol, supplements, age-restricted, or regulated products by region.
* **Honour licensing agreements** – Limit digital downloads and licensed content to permitted territories.
* **Build country-specific catalogs** – Show a tailored product range to each market automatically.
* **Reduce chargebacks** – Prevent orders you would only have to cancel and refund anyway.

= 3 Restriction Modes =

**Hide Completely**
Product disappears from shop pages, search results, and category listings. The direct product URL returns a 404 page. Optionally redirect restricted visitors to any custom URL instead.

**Hide from Catalog – Allow Direct URL**
Product is hidden from the shop and search but remains accessible via its direct link. Useful for distributors or B2B partners in restricted regions. A single toggle lets direct-URL visitors purchase too.

**Show Restriction Message**
Product stays visible in the catalog. On the product page, the Add to Cart button and price are replaced with your custom HTML message – ideal for explaining regional availability without hiding the product entirely.

= Key Features =

* **Per-product country rules** – Set include or exclude rules on any product from the product edit screen, with a searchable country selector and live rule summary.
* **Include or Exclude mode** – Whitelist specific countries (only they can buy) or blacklist countries (everyone except them can buy).
* **Custom restriction message** – Write your own HTML message with links, bold text, or any WordPress-allowed markup.
* **Configurable message position** – Place the restriction notice above the title, below the title, after the description, or before product meta.
* **Redirect on restriction** – In Hide mode, send restricted visitors to any URL instead of a 404 page.
* **Cart & checkout protection** – Restricted products added to cart before a rule was applied are automatically removed, with a clear customer notice.
* **Server-side add-to-cart validation** – Blocks direct POST, REST API, and AJAX add-to-cart attempts. Not just client-side.
* **Smart country detection (6 layers)** – Shipping address → billing address → WC session → transient cache → MaxMind GeoLite2 → store base country. No manual input needed.
* **Cloudflare IP support** – Reads `CF-Connecting-IP` automatically for sites behind Cloudflare.
* **Force Geolocation option** – Ignore saved addresses and always detect country from current IP.
* **Session & transient caching** – Country detection results cached per session. Zero repeated geolocation lookups.
* **Admin debug toolbar** – Shows detected country, IP address, detection source, and a settings link. Visible to admins only.
* **Test as country** – Preview your store as a visitor from any country from the debug toolbar. Admin-only, with an admin-bar reminder so you never forget you are testing.
* **Country content shortcodes** – `[geoblock_show]`, `[geoblock_hide]` and `[geoblock_country]` show or hide any content by visitor country, on any page or post.
* **Region presets** – Add the EU, EEA, North America, Nordics, GCC, ASEAN or Australia & NZ to a rule in one click.
* **Message placeholders** – Use `{country_name}`, `{country}` and `{country_flag}` in your restriction message.
* **Search bot exemption** – Optionally let Googlebot, Bingbot and link-preview crawlers always see restricted products so Hide mode cannot hurt your search visibility.
* **Admin bypass** – Optionally never restrict administrators and shop managers.
* **Setup health check** – A Status tab that checks geolocation, country data source, page caching and your detected country.
* **Shortcode support** – `[geoblock_product_message]` renders the restriction message in any page builder, custom template, Elementor, or Divi.
* **HPOS compatible** – Fully supports WooCommerce High-Performance Order Storage.
* **Translation ready** – Complete `.pot` file included. Compatible with Loco Translate and WPML String Translation.

= Plugin Compatibility =

Built-in compatibility for 6 popular plugins – no configuration needed:

* **Price Based on Country for WooCommerce** – Restriction message takes priority over country-based pricing on restricted products.
* **WPML** – Rules set on the original-language product automatically apply to all translated copies. No duplicate rules per language.
* **WooCommerce Product Bundles** – Restricting the bundle parent blocks the entire bundle from purchase.
* **WooCommerce Subscriptions** – Both `subscription` and `variable-subscription` product types fully supported.
* **WP Rocket** – Product pages automatically excluded from page cache so every visitor gets the correct restricted or unrestricted version.
* **Speed Optimizer by SiteGround** – Product pages bypass SiteGround's dynamic cache via `SGCACHENOCACHE` and `sgo_bypass_cache`.

= Use Cases =

* Block alcohol or age-restricted products from countries where they are prohibited by law
* Restrict physical goods to only the countries you ship to
* Limit digital downloads to licensed territories
* Hide out-of-stock regional variants from international markets
* Display a "contact us to order in your region" message instead of hiding the product
* Build exclusive country-specific product catalogs for different markets

= Shortcode =

Display the restriction message anywhere on your site using:
`[geoblock_product_message]`

**Attributes:**
* `id` – Product ID to check. Defaults to the current product in the loop.
* `class` – Extra CSS class(es) added to the message wrapper div.

**Examples:**
`[geoblock_product_message id=123]`
`[geoblock_product_message id=123 class="my-notice highlight"]`

= Country Content Shortcodes =

Show or hide any content by the visitor's country:

`[geoblock_show countries="US,CA"]Free shipping to North America[/geoblock_show]`
`[geoblock_hide countries="EU"]Prices exclude VAT[/geoblock_hide]`
`[geoblock_country format="flag_name"]`

**Attributes:**
* `countries` – Comma-separated country codes and/or region names: EU, EEA, NORTH_AMERICA, NORDICS, GCC, ASEAN, ANZ.
* `format` (`[geoblock_country]` only) – `name` (default), `code`, `flag` or `flag_name`.

Pages that use these shortcodes are marked as not cacheable (`DONOTCACHEPAGE`) so one country never sees another country's version. Use the `geoblock_shortcode_disable_cache` filter to turn this off.

= How It Works =

1. Install and activate the plugin.
2. Go to **WooCommerce > GeoBlock** and choose a restriction mode.
3. Open any product and find the **GeoBlock - Country Restrictions** meta box.
4. Select Include or Exclude, choose your countries, and save.
5. GeoBlock automatically detects each visitor's country and applies your rules in real time.

= Requirements =

* WordPress 6.9 or higher
* WooCommerce 7.0 or higher
* PHP 7.4 or higher
* WooCommerce Geolocation must be enabled: **WooCommerce Settings > General > Default customer location > Geolocate**
* For IP lookups, either a free MaxMind license key (**WooCommerce > Settings > Integration**) or a host/CDN that passes the visitor's country, such as Cloudflare

= GeoBlock Pro =

The free plugin is fully working. GeoBlock Pro adds bulk category rules, payment gateway restrictions by country, variation-level rules and an analytics dashboard.

[See GeoBlock Pro](https://windcodex.com/product/woocommerce-country-restriction-plugin/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=geoblock)

= More Free Plugins from WindCodex =

* [ReturnDesk](https://wordpress.org/plugins/windcodex-returndesk/)
* [ScraperBlock](https://wordpress.org/plugins/windcodex-scraperblock/)
* [SwitchUser](https://wordpress.org/plugins/windcodex-switchuser/)
* [Ops](https://wordpress.org/plugins/windcodex-ops/)
* [Forge](https://wordpress.org/plugins/windcodex-forge/)

= Privacy =

GeoBlock uses the visitor's IP address solely to determine their country. No personal data is stored permanently. Detection results are cached in WooCommerce session storage and WordPress transients with a 1-hour TTL. No data is sent to external servers. This plugin does not collect, sell, or share any visitor data.

== Installation ==

**From your WordPress dashboard:**

1. Go to **Plugins > Add New**.
2. Search for **WindCodex GeoBlock**.
3. Click **Install Now**, then **Activate**.

**Manual installation:**

1. Download the plugin ZIP file.
2. Upload the `windcodex-geoblock` folder to `/wp-content/plugins/`.
3. Activate through the **Plugins** screen in WordPress.

**After activation:**

1. Go to **WooCommerce > Settings > General** and set "Default customer location" to **Geolocate** or **Geolocate (with page caching support)**.
2. Go to **WooCommerce > GeoBlock Restrictions** to choose your global restriction mode.
3. Edit any product and use the **GeoBlock - Country Restrictions** meta box to set rules.

== Frequently Asked Questions ==

= Does this plugin require any API key or account? =

No paid service or GeoBlock account is needed. GeoBlock uses WooCommerce's own geolocation. If your host or CDN passes the visitor's country (for example Cloudflare), that works with no extra setup. Otherwise WooCommerce looks up the country in the MaxMind GeoLite2 database, which needs a free MaxMind license key under **WooCommerce > Settings > Integration**. The lookup runs on your own server. Customers with a saved shipping or billing address are detected from that address either way.

= Which countries can I block or restrict? =

All countries supported by WooCommerce – the same full list used in WooCommerce shipping and tax settings. You can restrict any single country or any combination of countries on each product independently.

= How do I block a specific country from purchasing a product? =

Open the product in your WordPress admin, find the **GeoBlock - Country Restrictions** meta box, select **Exclude**, choose the countries you want to block, and save. That country's visitors will be blocked immediately.

= Can I restrict products to only specific countries (whitelist)? =

Yes. Select **Include** mode in the meta box and choose the countries that are allowed to purchase. All other countries will be restricted automatically.

= Will this work with page caching plugins like WP Rocket or LiteSpeed? =

Yes. Set WooCommerce's "Default customer location" to **Geolocate (with page caching support)** (AJAX mode). Country detection happens via AJAX after page load, so it works correctly on fully cached pages. GeoBlock also includes a built-in WP Rocket compatibility layer that excludes product pages from cache.

= What happens if WooCommerce geolocation is not enabled? =

GeoBlock shows a warning banner in the plugin settings page. Without geolocation, guest visitors fall back to the store's base country and restrictions will not apply to them. Logged-in customers with a saved shipping or billing address are still detected correctly.

= Does this work with variable products? =

Yes. Country rules are set at the parent product level and automatically apply to all variations. In Hide and Catalog Only modes, the entire product including all variations is restricted. In Message mode, variation dropdowns and the Add to Cart button are both hidden on the product page.

= What if a customer uses a VPN? =

GeoBlock detects country based on IP address. A customer using a VPN may appear to be in a different country. For stores where VPN circumvention is a concern, enable the "Force Geolocation" option, which ignores saved addresses and always uses current IP detection.

= What happens if a restricted product is already in a customer's cart? =

GeoBlock automatically removes restricted products from the cart when the customer visits the cart or checkout page, and displays a clear notice explaining why. Checkout is also blocked as a final safety net even if the cart page was skipped.

= Can I set different country rules for different products? =

Yes. Each product has its own independent set of rules. Product A can be restricted to Europe only, Product B excluded from one specific country, and Product C open to everyone – all at the same time.

= Does geo blocking affect SEO or Google indexing? =

Search engine crawlers are detected by IP like any other visitor. Googlebot crawls mostly from US IP addresses, so if you use **Hide completely** for the US, Google may see a 404 and drop that product from its index. To keep restricted products indexed, turn on **Never restrict search engine and link-preview bots** in the Advanced tab, or use **Show Restriction Message** or **Hide from Catalog** for countries your main crawlers come from. Products open to the crawler's country are indexed normally.

= Can I show or hide normal page content by country? =

Yes. Wrap content in `[geoblock_show countries="US,CA"]...[/geoblock_show]` to show it only to those countries, or `[geoblock_hide countries="EU"]...[/geoblock_hide]` to show it to everyone else. You can use country codes or region names such as EU, EEA and NORDICS.

= How do I preview my store as a visitor from another country? =

Turn on Debug Mode in **WooCommerce > GeoBlock Restrictions > Advanced**, then use the "Test as" picker in the toolbar at the bottom of your site. Only you see it, and an admin-bar notice reminds you while it is active.

= Is this plugin GDPR compliant? =

GeoBlock uses the visitor's IP address only to work out their country, keeps the result for one hour (WooCommerce session and WordPress transient), and sends no data to external services. Whether your whole store meets GDPR depends on your setup, so mention country detection in your privacy policy and check with your own advisor.

= Does this work with WooCommerce Subscriptions? =

Yes. GeoBlock's compatibility layer hooks into WooCommerce Subscriptions' own purchasability filters and fully supports both `subscription` and `variable-subscription` product types.

= Can I use this with Elementor, Divi, or other page builders? =

Yes. Use the `[geoblock_product_message id=PRODUCT_ID]` shortcode in any page builder element or custom template to display the restriction message anywhere on your site.

= How do I test that country restrictions are working correctly? =

Enable Debug Mode in **WooCommerce > GeoBlock Restrictions > Advanced**. An admin-only debug toolbar will appear at the bottom of every frontend page showing the detected country, IP address, and detection method. You can also temporarily set your own country as a restricted country to verify the hide or message behaviour.

= Does the "Hide completely" mode still show the product on direct URL? =

No. In Hide completely mode, the direct product URL returns a 404 page. You can also configure a redirect URL to send restricted visitors to a custom page (like a contact or coming-soon page) instead of a 404.

= How is country detection performed? =

GeoBlock uses a 6-layer detection chain: (1) shipping address, (2) billing address, (3) WooCommerce session, (4) WordPress transient cache, (5) MaxMind GeoLite2 database via WooCommerce's built-in engine, (6) store base country fallback. Results are cached per session to avoid repeated lookups.

= Does GeoBlock work with WooCommerce Blocks? =

Yes. WooCommerce Blocks compatibility is declared. Cart and checkout protection applies regardless of whether classic or block-based cart and checkout is used.

== Screenshots ==

1. **Settings Page - General Tab** – Choose restriction mode (Hide, Catalog Only, or Message), configure redirect URL, and write your custom restriction message.
2. **Settings Page - Advanced Tab** – Force Geolocation and Debug Mode toggles.
3. **Product Meta Box** – Per-product Include/Exclude country rule with searchable country selector and live rule summary preview.

== Changelog ==

= 1.1.0 =
* Added: `[geoblock_show]`, `[geoblock_hide]` and `[geoblock_country]` shortcodes to show or hide any content by visitor country.
* Added: "Test as country" picker in the admin debug toolbar, plus an admin-bar reminder while testing.
* Added: Region presets (EU, EEA, North America, Nordics, GCC, ASEAN, Australia & NZ) on the product country selector.
* Added: `{country_name}`, `{country}` and `{country_flag}` placeholders in the restriction message.
* Added: Setting to never restrict search engine and link-preview bots.
* Added: Setting to never restrict administrators and shop managers.
* Added: Status tab with a setup health check.
* Added: `geoblock_region_groups`, `geoblock_is_exempt_visitor`, `geoblock_crawler_pattern` and `geoblock_shortcode_disable_cache` filters.
* Fixed: The debug toolbar could not be switched off from the settings page.

= 1.0.3 =
* Added: "Docs" link next to "Settings" on the Plugins screen.
* Added: Support Forum link in the Help menu.
* Improved: Refreshed Help menu design. The menu now closes with the Escape key and is correctly announced to screen readers.
* Improved: Uninstalling GeoBlock now removes all of its settings and per-product restriction rules. If GeoBlock Pro is active, nothing is removed, because Pro uses the same settings and rules.

= 1.0.2 =
* Added: Loads plugin translations via `load_plugin_textdomain()` for full compatibility with translation plugins (Loco Translate, WPML String Translation) and manually installed language packs.
* Updated: Regenerated the `.pot` translation template with the latest translatable strings.

= 1.0.1 =
* Improved: Settings page UI for better usability.
* Added: Admin review request notice.
* Added: Pro features notice.

= 1.0.0 =
* Initial release.
* Three restriction modes: Hide completely, Hide from catalog (Catalog Only), Show restriction message.
* Per-product Include/Exclude country rules with searchable Select2 country selector.
* Custom restriction message with HTML support and configurable position on product page (4 positions).
* Redirect URL option for Hide completely mode.
* Allow purchase via direct URL toggle for Catalog Only mode.
* 6-layer country detection: shipping address → billing address → WC session → transient cache → MaxMind GeoLite2 → store base.
* Cloudflare `CF-Connecting-IP` header support for reverse-proxied sites.
* Session and transient caching for geolocation performance (1-hour TTL).
* Force Geolocation option to ignore saved addresses and always use IP detection.
* Cart and checkout protection – restricted products auto-removed if added before rule was applied.
* Server-side add-to-cart validation via `woocommerce_add_to_cart_validation` blocks API/AJAX bypass attempts.
* Variation ID parent resolution – rules on parent product correctly block all child variations.
* `[geoblock_product_message]` shortcode for page builder and custom template compatibility.
* Admin debug toolbar (admin-only) showing country, IP, and detection source.
* AJAX save and reset with toast notifications and sticky footer save bar.
* HPOS (High-Performance Order Storage) compatibility declared.
* WooCommerce Blocks compatibility.
* Built-in compatibility: Price Based on Country, WPML, WooCommerce Product Bundles, WooCommerce Subscriptions, WP Rocket, Speed Optimizer by SiteGround.
* Translation-ready with complete `.pot` file included.

== Upgrade Notice ==

= 1.1.0 =
Adds country content shortcodes, a Test as country picker, region presets, a search bot exemption and a setup health check. No database changes – safe to update.

= 1.0.3 =
Adds a Docs link on the Plugins screen and Help menu improvements. No database changes – safe to update.

= 1.0.2 =
Loads plugin translations for full compatibility with translation plugins and language packs. No database changes – safe to update.

= 1.0.1 =
Adds inline Pro upsell banner, review request notice, and help button in the settings header. No database changes – safe to update.

= 1.0.0 =
Initial release – no upgrade steps required.
 