=== WooCommerce Smart Filters ===
Contributors: mazharbaig
Tags: woocommerce, filters, ajax, product filter, price slider, rating, search
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce Smart Filters adds a flexible, AJAX-powered sidebar filter to your shop or any page via the [smart_filters] shortcode. Buyers can filter by:

* Categories  
* Brands  
* Product Tags  
* Minimum Rating  
* Price Range (with slider and numeric inputs)  
* Product name or SKU search  

Filters update the product list without page reload and the URL is kept in sync for easy sharing or bookmarking.

== Description ==

WooCommerce Smart Filters provides a clean, Bootstrap-styled sidebar filter for WooCommerce shops.  
It offers:

1. **Category Filter** – Enable/disable, choose which categories appear.  
2. **Brand Filter** – Works with any custom “product_brand” taxonomy.  
3. **Tag Filter** – Filter by product tags.  
4. **Rating Filter** – Minimum-rating dropdown (1★ – 5★).  
5. **Price Range** – jQuery UI slider + min/max inputs.  
6. **Search Box** – Live AJAX search by product title or SKU.  
7. **Shortcode** – Insert anywhere: `[smart_filters]`.  
8. **Responsive & AJAX** – No page reloads, works on Shop & custom pages, URL reflects all filters.  
9. **Admin Settings** – Toggle each filter, set defaults, choose pages.

== Installation ==

1. Upload the `woocommerce-smart-filters` folder to your `/wp-content/plugins/` directory.  
2. Activate the plugin through the **Plugins** menu in WordPress.  
3. Go to **WooCommerce → Smart Filters** to configure:
   * Enable/disable each filter type.  
   * Select which categories, brands, or tags to show.  
   * Set products per page.  
   * Define default price range.  
4. Ensure your theme has a sidebar on the Shop page OR place `[smart_filters]` on any page.

== Frequently Asked Questions ==

= How do I display the filters on a custom page? =
Enable **Show Filter On Pages** in settings and check the desired page. Or add the `[smart_filters]` shortcode into any page content.

= What if I don’t use a “product_brand” taxonomy? =
The Brand filter block will automatically hide if `product_brand` doesn’t exist.

= Can I change the price slider range? =
Yes—set **Default Min Price** and **Default Max Price** in admin. Users can still adjust within that range.

= Why aren’t URLs updating when I filter? =
Make sure your theme’s permalinks are enabled (non-plain). The plugin builds friendly pagination & query strings.

= How do I style the filter sidebar? =
You can override the `templates/filter-form.php` in your theme or add CSS targeting `.wcsf-sidebar` and `.filter-block`.

== Screenshots ==
1. **Admin Settings** – Toggle filters & select terms.  
2. **Shop Sidebar** – Categories, Brands, Tags, Rating, Price slider, Search box.  
3. **AJAX Results** – Products update instantly, URL reflects filters.  
4. **Shortcode Usage** – `[smart_filters]` on a custom page.

### 📱 Responsive & Mobile-Ready

- **Fluid grid layout**: Uses Bootstrap’s grid classes so the filter sidebar and product grid stack naturally on smaller screens.  
- **Collapsible sidebar**: On narrow viewports, the sidebar collapses into a toggleable drawer—keeping your product listing front and center.  
- **Touch-optimized controls**: The jQuery UI price slider and dropdowns are fully touch-friendly on iOS and Android devices.  
- **Adaptive breakpoints**: Custom CSS ensures comfortable padding, font sizes, and element spacing across all common device widths (<576px, ≥576px, ≥768px, ≥992px).  
- **Bookmarkable filters**: Even on mobile, the URL stays in sync so customers can share or revisit a pre-fil
