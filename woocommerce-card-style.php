<?php
/*
Plugin Name: WooCommerce Smart Filters
Description: Woo-style filter sidebar + Bootstrap grid on shop pages and via the [smart_filters] shortcode (no parameters).
Version:     1.2.4
Author:      mazhar baig
Text Domain: wc-smart-filters
*/

if ( ! defined( 'ABSPATH' ) ) exit;

/* ─────────────────────────────────────────────────────────── 0. Assets */
add_action( 'wp_enqueue_scripts', function () {


	wp_enqueue_script(
        'wcsf-ajax',
        plugins_url( 'assets/js/wcsf-ajax.js', __FILE__ ),
        [ 'jquery' ],
        '1.0',
        true
    );

    wp_localize_script( 'wcsf-ajax', 'wcsfAjax', [
        'url'                 => admin_url( 'admin-ajax.php' ),
        'nonce'               => wp_create_nonce( 'wcsf_filter' ),
        'attributeTaxonomies' => (array) get_option( 'wcsf_attribute_filter_list', [] ),
        'priceDefaults' => [
            'min' => floatval( get_option( 'wcsf_price_filter_min', 0 ) ),
            'max' => floatval( get_option( 'wcsf_price_filter_max', 1000 ) ),
        ],
    ] );

	// Google Font (optional)
	wp_enqueue_style(
		'wcsf-font-poppins',
		'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap',
		[],
		null
	);

	// Bootstrap 5 – load only if theme hasn't already
	if ( ! wp_style_is( 'bootstrap', 'enqueued' ) ) {
		wp_enqueue_style(
			'wcsf-bootstrap',
			'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
			[],
			'5.3.3'
		);
	}
	if ( ! wp_script_is( 'bootstrap', 'enqueued' ) ) {
		wp_enqueue_script(
			'wcsf-bootstrap',
			'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
			[ 'jquery' ],
			'5.3.3',
			true
		);
	}

	// Card tweaks
	wp_enqueue_style(
		'wcsf-cards',
		plugins_url( 'assets/css/card-style.css', __FILE__ ),
		[ 'wcsf-bootstrap' ],
		'1.2.4'
	);

    // CSS file
    wp_enqueue_style(
        'wcsf-offcanvas',
        plugins_url( 'assets/css/wcsf-offcanvas.css', __FILE__ ),
        [ 'wcsf-cards' ],   // make sure this depends on your main plugin CSS
        '1.0'
    );
    
    // JS file
    wp_enqueue_script(
        'wcsf-offcanvas',
        plugins_url( 'assets/js/wcsf-offcanvas.js', __FILE__ ),
        [ 'jquery' ],
        '1.0',
        true
    );
  

	wp_add_inline_style( 'wcsf-cards', '
		.wcsf-sidebar                    { background:#f8f8f8;border-radius:8px;padding:1rem; }
		.wcsf-product-card .card-img-top { max-height:250px;object-fit:cover; }
		.wcsf-product-card .price        { font-weight:600;color:#c00; }
		ul.products { list-style:none;margin:0;padding:0 }
	' );
} );



/* ──────────────────────────────────────────────────────── 1. Widget area */
add_action( 'widgets_init', function () {
	register_sidebar( [
		'name'          => __( 'Product Filters', 'wc-smart-filters' ),
		'id'            => 'wcsf_sidebar',
		'before_widget' => '<div class="filter-widget %2$s mb-4">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="mb-3">',
		'after_title'   => '</h4>',
	] );
} );

add_action( 'wp_enqueue_scripts', 'wcsf_enqueue_slider_assets', 25 );
function wcsf_enqueue_slider_assets() {
    if ( is_admin() ) return;

    // only load on shop or pages with our shortcode
    if ( is_shop() || has_shortcode( get_post()->post_content ?? '', 'smart_filters' ) ) {
        // jQuery UI slider (bundled with WP core)
        wp_enqueue_script('jquery-ui-slider');
        // a basic jQuery UI theme (you can self-host or pick another)
        wp_enqueue_style('wcsf-jquery-ui','https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        // your slider init (depends on jquery-ui-slider + your existing wcsf-ajax.js)
        wp_enqueue_script(
          'wcsf-slider',
          plugins_url('assets/js/slider.js', __FILE__),
          ['jquery','jquery-ui-slider','wcsf-ajax'],
          '1.0',
          true
        );
        $min_price = floatval( get_option( 'wcsf_price_filter_min', 0 ) );
        $max_price = floatval( get_option( 'wcsf_price_filter_max', 1000 ) );
        
        // then pass them as a PHP array
        wp_localize_script(
          'wcsf-slider',
          'wcsfPrice',
          [
            'min' => $min_price,
            'max' => $max_price,
          ]
        );
    }
}



function wcsf_render_sidebar() {
    // Only output desktop sidebar if enabled in settings
    if ( ! get_option('wcsf_show_category_filter') ) {
        return;
    }

    // 1) MOBILE “Filters” toolbar (only < md)
    ?>
    <nav class="wcsf-mobile-toolbar d-md-none">
      <button id="wcsf-mobile-filters-btn" class="btn btn-primary w-100">
        <?php esc_html_e( 'Filters', 'wc-smart-filters' ); ?>
      </button>
    </nav>
    <?php

    // 2) OFF-CANVAS PANEL (only < md)
    ?>
    <div class="wcsf-offcanvas-overlay"></div>
    <aside class="wcsf-offcanvas-panel d-md-none">
      <button class="wcsf-offcanvas-close">&times;</button>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0"><?php esc_html_e( 'Filters', 'wc-smart-filters' ); ?></h5>
        <button id="wcsf-clear-filters" class="btn btn-sm btn-link">
          <?php esc_html_e( 'Clear All', 'wc-smart-filters' ); ?>
        </button>
      </div>
      <?php
        // our same shared template…
        wc_get_template(
          'filter-form.php',
          [],
          '',
          plugin_dir_path( __FILE__ ) . 'templates/'
        );
      ?>
      <button id="wcsf-apply-filters" class="btn btn-primary w-100 mt-3">
        <?php esc_html_e( 'Apply Filters', 'wc-smart-filters' ); ?>
      </button>
    </aside>
    <?php

    // 3) DESKTOP SIDEBAR (only ≥ md)
    echo '<aside class="col-12 col-md-3 mb-4 mb-md-0 wcsf-sidebar d-none d-md-block">';
      dynamic_sidebar('wcsf_sidebar');
      wc_get_template(
        'filter-form.php',
        [],
        '',
        plugin_dir_path( __FILE__ ) . 'templates/'
      );
    echo '</aside>';
}



/* ──────────────────────────────────────────── 3. Shop / archive wrappers */
add_action( 'woocommerce_before_main_content', function () {
	echo '<div class="container my-5"><div class="row">';

	wcsf_render_sidebar();
	echo '<section class="col-12 col-md-9">';
}, 5 );

add_action( 'woocommerce_after_main_content', function () {
	echo '</section></div></div>';
}, 50 );

/* Wrap the core product loop */
add_action( 'woocommerce_before_shop_loop', function () {
	echo '<div id="wcsf-products">';
}, 5 );            // after WC prints the result-count, before <ul class=products>

add_action( 'woocommerce_after_shop_loop', function () {
	echo '</div>';
}, 20 );  

add_filter( 'loop_shop_per_page', function () {
    return (int) get_option('wcsf_products_per_page', 3);
}, 20 );
/* ───────────────────────────────────────────────────────── 4. Shortcode */
add_shortcode( 'smart_filters', function () {

    if ( ! class_exists( 'WooCommerce' ) ) {
        return '<p>' . esc_html__( 'WooCommerce must be active to use this shortcode.', 'wc-smart-filters' ) . '</p>';
    }

	$show = get_option('wcsf_show_category_filter');
    $enabled_pages = (array) get_option('wcsf_filter_pages', []);
    $current_id = is_shop() ? wc_get_page_id('shop') : ( is_page() ? get_the_ID() : 0 );

    if ( ! $show || ! in_array( $current_id, $enabled_pages ) ) {
        return ''; // No output if not enabled here
    }

    ob_start();

    /* opens <div class="container"><div class="row"><aside>…<section> */
    do_action( 'woocommerce_before_main_content' );

        // Result count (optional)
        echo '<div class="woocommerce-result-count mb-3">';
        woocommerce_result_count();
        echo '</div>';

        // Product grid with native WC pagination
	//	echo '<div id="wcsf-products">';

		// product loop + pagination
		$limit = isset($args['posts_per_page'])
    ? intval($args['posts_per_page'])
    : intval(get_option('wcsf_products_per_page', 3));
echo do_shortcode( '[products limit="' . $limit . '" columns="4" paginate="true"]' );

	/* ———————— close wrapper ———————— */
	//echo '</div>';

    /* closes </section></div></div> */
    do_action( 'woocommerce_after_main_content' );

    return ob_get_clean();
} );

/* ─────────────────────────────── 5. Load Woo styles when shortcode used */
add_action( 'wp_enqueue_scripts', 'wcsf_maybe_enqueue_wc_assets', 20 );

function wcsf_maybe_enqueue_wc_assets() {

	if ( is_admin() || is_feed() ) {
		return;
	}

	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( has_shortcode( $post->post_content, 'smart_filters' ) ) {

		if ( class_exists( 'WC_Frontend_Scripts' ) && method_exists( 'WC_Frontend_Scripts', 'enqueue_scripts' ) ) {
			WC_Frontend_Scripts::enqueue_scripts();        // WooCommerce < 9-x
		}

		foreach ( [
			'woocommerce-layout',
			'woocommerce-smallscreen',
			'woocommerce-general',
			'woocommerce-blocks-style',
		] as $handle ) {
			if ( wp_style_is( $handle, 'registered' ) && ! wp_style_is( $handle, 'enqueued' ) ) {
				wp_enqueue_style( $handle );
			}
		}
	}
}

/* ─────────────────────────────────── 6. REMOVE THE THEME'S RIGHT SIDEBAR */
/*  Clears every sidebar except our own on pages/posts that contain the
    [smart_filters] shortcode.  Works with any theme.                      */
add_filter( 'sidebars_widgets', function ( $sidebars ) {

	$post = is_singular() ? get_post() : null;
	if ( ! ( $post instanceof WP_Post ) ) {
		return $sidebars;
	}

	if ( has_shortcode( $post->post_content, 'smart_filters' ) ) {

		foreach ( $sidebars as $id => $widgets ) {

			// Keep WP inactive area + the filter sidebar; empty everything else
			if ( ! in_array( $id, [ 'wp_inactive_widgets', 'wcsf_sidebar' ], true ) ) {
				$sidebars[ $id ] = [];     // remove all widgets from this sidebar
			}
		}
	}

	return $sidebars;
}, 20 );


add_action( 'wp_ajax_wcsf_filter',        'wcsf_ajax_filter' );
add_action( 'wp_ajax_nopriv_wcsf_filter', 'wcsf_ajax_filter' );

/**
 * AJAX handler for filtering products.
 */
function wcsf_ajax_filter() {
    // 1. Security check
    if ( ! wp_verify_nonce( $_POST['security'] ?? '', 'wcsf_filter' ) ) {
        wp_send_json_error();
    }

    // 2. Pagination
    $paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;

    // 3. Load settings
    $show_cat    = (bool) get_option( 'wcsf_show_category_filter' );
    $show_brand  = (bool) get_option( 'wcsf_show_brand_filter' );
    $show_tag    = (bool) get_option( 'wcsf_show_tag_filter' );
    $show_rating = (bool) get_option( 'wcsf_show_rating_filter' );
    $show_price  = (bool) get_option( 'wcsf_show_price_filter' );
    $show_search = (bool) get_option( 'wcsf_show_search_box' );
   

    // 4. Sanitize inputs or default
    $cats       = ! empty( $_POST['cats'] )   ? array_map( 'sanitize_text_field', (array) $_POST['cats'] )   : [];
    $brands     = ! empty( $_POST['brands'] ) ? array_map( 'sanitize_text_field', (array) $_POST['brands'] ) : [];
    $tags       = ! empty( $_POST['tags'] )   ? array_map( 'sanitize_text_field', (array) $_POST['tags'] )   : [];
    $min_rating = ! empty( $_POST['min_rating'] ) ? intval( $_POST['min_rating'] ) : 0;
    $search     = $show_search && isset( $_POST['search'] )
                  ? sanitize_text_field( $_POST['search'] )
                  : '';
    $default_min = floatval( get_option( 'wcsf_price_filter_min', 0 ) );
    $default_max = floatval( get_option( 'wcsf_price_filter_max', 1000 ) );

    $min_price  = $show_price && isset( $_POST['min_price'] )
                  ? floatval( $_POST['min_price'] )
                  : $default_min;
    $max_price  = $show_price && isset( $_POST['max_price'] )
                  ? floatval( $_POST['max_price'] )
                  : $default_max;

    // 5. Base query args
    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => (int) get_option( 'wcsf_products_per_page', 12 ),
        'paged'          => $paged,
    ];

    // 6. Build tax_query
    $tax_query = [ 'relation' => 'AND' ];

    if ( $show_cat && taxonomy_exists( 'product_cat' ) && ! empty( $cats ) ) {
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $cats,
        ];
    }

    if ( $show_brand && taxonomy_exists( 'product_brand' ) && ! empty( $brands ) ) {
        $tax_query[] = [
            'taxonomy' => 'product_brand',
            'field'    => 'slug',
            'terms'    => $brands,
        ];
    }

    if ( $show_tag && taxonomy_exists( 'product_tag' ) && ! empty( $tags ) ) {
        $tax_query[] = [
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => $tags,
        ];
    }

      // ───── INSERT HERE ─────
    // Pull the list of attribute taxonomies the admin selected
    $attrs = (array) get_option('wcsf_attribute_filter_list', []);
    foreach ( $attrs as $tax ) {
        if ( taxonomy_exists( $tax ) && ! empty( $_POST[ $tax ] ) ) {
            $terms = array_map( 'sanitize_text_field', (array) $_POST[ $tax ] );
            $tax_query[] = [
                'taxonomy' => $tax,
                'field'    => 'slug',
                'terms'    => $terms,
            ];
        }
    }
    // ───────────────────────

    if ( count( $tax_query ) > 1 ) {
        $args['tax_query'] = $tax_query;
    }

    // 7. Build meta_query
    $meta_query = [];

    if ( $show_rating && $min_rating > 0 ) {
        $meta_query[] = [
            'key'     => '_wc_average_rating',
            'value'   => $min_rating,
            'compare' => '>=',
            'type'    => 'DECIMAL',
        ];
    }

    if ( $show_price && ( $min_price != $default_min || $max_price != $default_max ) ) {
        $meta_query[] = [
            'key'     => '_price',
            'value'   => [ $min_price, $max_price ],
            'compare' => 'BETWEEN',
            'type'    => 'NUMERIC',
        ];
    }

    if ( ! empty( $meta_query ) ) {
        $args['meta_query'] = $meta_query;
    }

    // 8. Text search (name or SKU)
    if ( $show_search && $search !== '' ) {
        $args['wcsf_search'] = $search;
        add_filter( 'posts_join',  'wcsf_search_join',  10, 2 );
        add_filter( 'posts_where', 'wcsf_search_where', 10, 2 );
    }

    // 9. Run the query
    $q = new WP_Query( $args );

    // 10. Clean up search filters
    if ( isset( $args['wcsf_search'] ) ) {
        remove_filter( 'posts_join',  'wcsf_search_join',  10 );
        remove_filter( 'posts_where', 'wcsf_search_where', 10 );
    }

    // 11. Output
    ob_start();

    if ( $q->have_posts() ) {
        wc_set_loop_prop( 'columns',      4 );
        wc_set_loop_prop( 'total',        $q->found_posts );
        wc_set_loop_prop( 'per_page',     $args['posts_per_page'] );
        wc_set_loop_prop( 'current_page', max( 1, $paged ) );

        echo '<div class="woocommerce-result-count mb-3">';
        woocommerce_result_count();
        echo '</div>';

        woocommerce_product_loop_start();
        while ( $q->have_posts() ) {
            $q->the_post();
            wc_get_template_part( 'content', 'product' );
        }
        woocommerce_product_loop_end();

        echo wcsf_build_pagination( [
            'total'   => $q->max_num_pages,
            'current' => $paged,
            'url'     => esc_url_raw( $_POST['current_url'] ?? '' ),
        ] );
    } else {
        echo '<p class="woocommerce-info">' . esc_html__( 'No products found', 'woocommerce' ) . '</p>';
    }

    wp_reset_postdata();

    // 12. Send JSON
    wp_send_json_success( [ 'html' => ob_get_clean() ] );
}

function wcsf_search_join( $join, $wp_query ) {
    global $wpdb;
    if ( $wp_query->get('wcsf_search') ) {
      // join postmeta for SKU
      $join .= " 
        LEFT JOIN {$wpdb->postmeta} AS sku_pm
          ON {$wpdb->posts}.ID = sku_pm.post_id
         AND sku_pm.meta_key = '_sku'
      ";
    }
    return $join;
  }
  
  function wcsf_search_where( $where, $wp_query ) {
    global $wpdb;
    if ( $search = $wp_query->get('wcsf_search') ) {
      $like = '%'.$wpdb->esc_like( $search ).'%';
      // match _either_ post_title _or_ sku_pm.meta_value
      $where .= $wpdb->prepare("
        AND (
          {$wpdb->posts}.post_title LIKE %s
          OR sku_pm.meta_value    LIKE %s
        )
      ", $like, $like );
    }
    return $where;
  }
  



  function wcsf_build_pagination( $args ) {
    $total       = max( 1, (int) $args['total'] );
    $current     = max( 1, (int) $args['current'] );
    $current_url = $args['url'];

    // 1) Remove any existing ?product-page=… from the URL
    $base_url = remove_query_arg( 'product-page', $current_url );

    // 2) Strip any /page/X/ from the path
    $base_url = preg_replace( '#/page/\d+(?=/|$)#', '', $base_url );

    // 3) Decide permalink structure:
    if ( strpos( $base_url, '/shop/' ) !== false ) {
        // Shop archive—use /page/X/
        $base   = untrailingslashit( $base_url ) . '/page/%#%/';
        $format = '';
    } else {
        // Custom page—use ?product-page=X or &product-page=X
        $base   = $base_url;
        $format = ( strpos( $base_url, '?' ) !== false ? '&' : '?' ) . 'product-page=%#%';
    }

    // 4) Generate links
    $links = paginate_links( [
        'base'      => $base . $format,
        'format'    => '',            // placeholder already in base
        'current'   => $current,
        'total'     => $total,
        'prev_text' => __( '&larr; Prev', 'woocommerce' ),
        'next_text' => __( 'Next &rarr;', 'woocommerce' ),
        'type'      => 'list',
    ] );

    if ( ! $links ) {
        return '';
    }

    return '<nav class="woocommerce-pagination" aria-label="' . esc_attr__( 'Product pagination', 'woocommerce' ) . '">' 
         . $links 
         . '</nav>';
}


// 1. Add Admin Menu
add_action('admin_menu', function () {
    add_submenu_page(
        'woocommerce',
        __('Smart Filters Settings', 'wc-smart-filters'),
        __('Smart Filters', 'wc-smart-filters'),
        'manage_options',
        'wc-smart-filters',
        'wcsf_settings_page'
    );
});

// 2. Register Settings
add_action('admin_init', function () {
    register_setting('wcsf_settings', 'wcsf_show_category_filter');
    register_setting('wcsf_settings', 'wcsf_category_filter_list'); 
    register_setting('wcsf_settings', 'wcsf_products_per_page');
	register_setting('wcsf_settings', 'wcsf_filter_pages');
	register_setting('wcsf_settings', 'wcsf_show_brand_filter');
    register_setting('wcsf_settings', 'wcsf_brand_filter_list'); 
    register_setting('wcsf_settings', 'wcsf_show_tag_filter');
    register_setting('wcsf_settings', 'wcsf_tag_filter_list');   
    register_setting('wcsf_settings', 'wcsf_show_rating_filter');

        // Price slider
    register_setting( 'wcsf_settings', 'wcsf_show_price_filter' );
    register_setting( 'wcsf_settings', 'wcsf_price_filter_min' );
    register_setting( 'wcsf_settings', 'wcsf_price_filter_max' );

    // Search box
    register_setting( 'wcsf_settings', 'wcsf_show_search_box' );

    register_setting('wcsf_settings', 'wcsf_show_attribute_filter');
    register_setting('wcsf_settings', 'wcsf_attribute_filter_list');   // array of taxonomy slugs
    register_setting( 'wcsf_settings', 'wcsf_filter_sort_option' );

     // ← REGISTER YOUR NEW BLOCK‐ORDER SETTING
     register_setting(
        'wcsf_settings',
        'wcsf_filter_block_order',
        [
            'sanitize_callback' => function( $input ) {
                // we expect an array of slug=>position
                if ( ! is_array($input) ) {
                    return [];
                }
                // cast every value to int, drop negatives
                return array_map( function($v){
                    return max( 0, intval($v) );
                }, $input );
            },
            'default' => [],  // default empty array
        ]
    );
});

// 3. Settings Page Output
function wcsf_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Smart Filters Settings', 'wc-smart-filters'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('wcsf_settings'); ?>
            <?php do_settings_sections('wcsf_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Show Category Filter?', 'wc-smart-filters'); ?></th>
                    <td>
                        <input type="checkbox" name="wcsf_show_category_filter" value="1" <?php checked(get_option('wcsf_show_category_filter')); ?> />
                        <label><?php esc_html_e('Enable the category filter sidebar?', 'wc-smart-filters'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Categories to Show', 'wc-smart-filters'); ?></th>
                    <td>
                        <?php
                        $cats = get_terms(['taxonomy'=>'product_cat', 'hide_empty'=>true]);
                        $sel = (array)get_option('wcsf_category_filter_list', []);
                        foreach ($cats as $cat) {
                            echo '<label style="margin-right:12px;"><input type="checkbox" name="wcsf_category_filter_list[]" value="' . esc_attr($cat->term_id) . '" ' . checked(in_array($cat->term_id, $sel), 1, 0) . '> ' . esc_html($cat->name) . '</label><br>';
                        }
                        ?>
                        <p class="description"><?php esc_html_e('Select which categories should be visible in the filter.', 'wc-smart-filters'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Products Per Page', 'wc-smart-filters'); ?></th>
                    <td>
                        <input type="number" min="1" max="48" name="wcsf_products_per_page" value="<?php echo esc_attr(get_option('wcsf_products_per_page', 3)); ?>" />
                        <p class="description"><?php esc_html_e('Number of products to show per page (pagination).', 'wc-smart-filters'); ?></p>
                    </td>
                </tr>
				<tr>
					<th scope="row"><?php esc_html_e('Show Filter On Pages', 'wc-smart-filters'); ?></th>
					<td>
						<?php
						$pages = get_pages([ 'post_status' => 'publish' ]);
						$shop_id = wc_get_page_id( 'shop' );
						$sel_pages = (array)get_option('wcsf_filter_pages', []);
						// Add shop page
						if ($shop_id && $shop_id > 0) {
							$shop = get_post($shop_id);
							if ($shop) {
								echo '<label><input type="checkbox" name="wcsf_filter_pages[]" value="' . $shop_id . '" ' . checked(in_array($shop_id, $sel_pages), 1, 0) . '> ' . esc_html($shop->post_title) . ' (Shop Page)</label><br>';
							}
						}
						foreach ($pages as $page) {
							// Don't repeat shop page
							if ($shop_id == $page->ID) continue;
							echo '<label><input type="checkbox" name="wcsf_filter_pages[]" value="' . $page->ID . '" ' . checked(in_array($page->ID, $sel_pages), 1, 0) . '> ' . esc_html($page->post_title) . ' (slug: ' . esc_html($page->post_name) . ')</label><br>';
						}
						?>
						<p class="description"><?php esc_html_e('Select the pages where you want to display the filter sidebar.', 'wc-smart-filters'); ?></p>
					</td>
				</tr>
				<tr>
            <th scope="row"><?php esc_html_e('Show Brand Filter?', 'wc-smart-filters'); ?></th>
            <td>
                <input type="checkbox" name="wcsf_show_brand_filter" value="1" <?php checked(get_option('wcsf_show_brand_filter')); ?> />
                <label><?php esc_html_e('Enable the brand filter sidebar?', 'wc-smart-filters'); ?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Brands to Show', 'wc-smart-filters'); ?></th>
            <td>
                <?php
                $brands = get_terms(['taxonomy'=>'product_brand', 'hide_empty'=>true]);
                $sel_brands = (array)get_option('wcsf_brand_filter_list', []);
                foreach ($brands as $brand) {
                    echo '<label style="margin-right:12px;"><input type="checkbox" name="wcsf_brand_filter_list[]" value="' . esc_attr($brand->term_id) . '" ' . checked(in_array($brand->term_id, $sel_brands), 1, 0) . '> ' . esc_html($brand->name) . '</label><br>';
                }
                ?>
                <p class="description"><?php esc_html_e('Select which brands should be visible in the filter.', 'wc-smart-filters'); ?></p>
            </td>
         </tr>
         <tr>
        <th scope="row"><?php esc_html_e('Show Tag Filter?', 'wc-smart-filters'); ?></th>
            <td>
                <input type="checkbox" name="wcsf_show_tag_filter" value="1" 
                <?php checked( get_option('wcsf_show_tag_filter') ); ?> />
                <label><?php esc_html_e('Enable the tag filter sidebar?', 'wc-smart-filters'); ?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Tags to Show', 'wc-smart-filters'); ?></th>
            <td>
                <?php
                $tags     = get_terms(['taxonomy'=>'product_tag','hide_empty'=>true]);
                $sel_tags = (array) get_option('wcsf_tag_filter_list', []);
                foreach($tags as $tag){
                echo '<label style="margin-right:12px;">
                    <input type="checkbox" 
                        name="wcsf_tag_filter_list[]" 
                        value="'.esc_attr($tag->term_id).'" '
                        .checked(in_array($tag->term_id,$sel_tags),1,false).
                    '> '.esc_html($tag->name).
                '</label><br>';
                }
                ?>
                <p class="description"><?php esc_html_e('Select which product tags appear in the filter.', 'wc-smart-filters'); ?></p>
            </td>
        </tr>

<!-- Show Rating Filter? -->
    <tr>
    <th scope="row"><?php esc_html_e('Show Rating Filter?', 'wc-smart-filters'); ?></th>
    <td>
        <input type="checkbox" name="wcsf_show_rating_filter" value="1"
        <?php checked( get_option('wcsf_show_rating_filter') ); ?> />
        <label><?php esc_html_e('Enable minimum-rating dropdown?', 'wc-smart-filters'); ?></label>
    </td>
    </tr>		

        <!-- PRICE SLIDER SETTINGS -->
        <tr>
        <th scope="row"><?php esc_html_e('Show Price Filter?', 'wc-smart-filters'); ?></th>
        <td>
            <input type="checkbox" name="wcsf_show_price_filter" value="1"
            <?php checked( get_option('wcsf_show_price_filter') ); ?> />
            <label><?php esc_html_e('Enable the price‐range slider?', 'wc-smart-filters'); ?></label>
        </td>
        </tr>
        <tr>
        <th scope="row"><?php esc_html_e('Default Min Price', 'wc-smart-filters'); ?></th>
        <td>
            <input type="number" name="wcsf_price_filter_min"
            value="<?php echo esc_attr( get_option('wcsf_price_filter_min', 0) ); ?>"
            step="0.01" min="0" />
        </td>
        </tr>
        <tr>
        <th scope="row"><?php esc_html_e('Default Max Price', 'wc-smart-filters'); ?></th>
        <td>
            <input type="number" name="wcsf_price_filter_max"
            value="<?php echo esc_attr( get_option('wcsf_price_filter_max', 1000) ); ?>"
            step="0.01" min="0" />
        </td>
        </tr>

        <!-- SEARCH BOX SETTINGS -->
        <tr>
        <th scope="row"><?php esc_html_e('Show Search Box?', 'wc-smart-filters'); ?></th>
        <td>
            <input type="checkbox" name="wcsf_show_search_box" value="1"
            <?php checked( get_option('wcsf_show_search_box') ); ?> />
            <label><?php esc_html_e('Enable product name/SKU search?', 'wc-smart-filters'); ?></label>
        </td>
        </tr>

        <tr>
            <th scope="row"><?php esc_html_e('Show Attribute Filters?', 'wc-smart-filters'); ?></th>
            <td>
                <input
                type="checkbox"
                name="wcsf_show_attribute_filter"
                value="1"
                <?php checked( get_option('wcsf_show_attribute_filter') ); ?>
                />
                <label><?php esc_html_e('Enable attribute filters?', 'wc-smart-filters'); ?></label>
            </td>
            </tr>
            <tr>
            <th scope="row"><?php esc_html_e('Which Attributes?', 'wc-smart-filters'); ?></th>
            <td>
                <?php
                // Get all registered product attribute taxonomies
                $taxonomies = wc_get_attribute_taxonomies();
                $sel = (array) get_option('wcsf_attribute_filter_list', []);
                foreach ( $taxonomies as $tax ) {
                // Convert WC attribute record -> taxonomy name: pa_{slug}
                $tax_name = wc_attribute_taxonomy_name( $tax->attribute_name );
                $label    = esc_html( $tax->attribute_label );
                printf(
                    '<label style="margin-right:12px;"><input type="checkbox" name="wcsf_attribute_filter_list[]" value="%1$s" %2$s> %3$s</label><br>',
                    esc_attr( $tax_name ),
                    checked( in_array( $tax_name, $sel, true ), true, false ),
                    $label
                );
                }
                ?>
                <p class="description"><?php esc_html_e('Select which product attributes to show as filters.', 'wc-smart-filters'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Filter Options Sort Order', 'wc-smart-filters'); ?></th>
            <td>
                <?php 
                $current = get_option('wcsf_filter_sort_option', 'name_asc');
                ?>
                <select name="wcsf_filter_sort_option">
                <option value="name_asc"   <?php selected( $current, 'name_asc' );   ?>><?php esc_html_e('Name A → Z','wc-smart-filters'); ?></option>
                <option value="name_desc"  <?php selected( $current, 'name_desc' );  ?>><?php esc_html_e('Name Z → A','wc-smart-filters'); ?></option>
                <option value="count_desc" <?php selected( $current, 'count_desc' ); ?>><?php esc_html_e('Count High → Low','wc-smart-filters'); ?></option>
                <option value="count_asc"  <?php selected( $current, 'count_asc' );  ?>><?php esc_html_e('Count Low → High','wc-smart-filters'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('How should your filter checkboxes be ordered?','wc-smart-filters'); ?></p>
            </td>
         </tr>

         <?php
// Build a list of all block slugs & labels
$blocks = [
  'categories' => __('Categories','wc-smart-filters'),
  'brands'     => __('Brands','wc-smart-filters'),
  'tags'       => __('Tags','wc-smart-filters'),
  'rating'     => __('Rating','wc-smart-filters'),
  'search'     => __('Search Box','wc-smart-filters'),
  'price'      => __('Price Range','wc-smart-filters'),
];
// Add any attribute taxonomies
foreach ( (array) get_option('wcsf_attribute_filter_list',[]) as $tax ) {
    $blocks[ $tax ] = ucwords( str_replace('_',' ', preg_replace('/^pa_/','',$tax)) );
}

// Fetch previously saved positions
$orders = (array) get_option('wcsf_filter_block_order', []);
?>

<?php foreach ( $blocks as $slug => $label ) : ?>
  <tr>
    <th scope="row"><?php echo esc_html( $label ); ?></th>
    <td>
      <input
        type="number"
        name="wcsf_filter_block_order[<?php echo esc_attr( $slug ); ?>]"
        value="<?php echo esc_attr( isset( $orders[ $slug ] ) ? $orders[ $slug ] : 0 ); ?>"
        min="0"
        style="width:5em;"
      />
      <p class="description">
        <?php esc_html_e( 'Position (1 = first; leave 0 to hide)', 'wc-smart-filters' ); ?>
      </p>
    </td>
  </tr>
<?php endforeach; ?>


        


        </table>
        <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_filter('loop_shop_per_page', function ($n) {
    return (int)get_option('wcsf_products_per_page', 3);
}, 20);
