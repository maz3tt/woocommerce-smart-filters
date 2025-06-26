<form class="wc-filter-form">
<?php
// 1. Categories block (your existing code, unchanged)
$cats_sel = isset($_GET['cats']) ? (array) $_GET['cats'] : [];
$allowed = (array)get_option('wcsf_category_filter_list', []);
$cats = get_terms([
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'include'    => $allowed ? $allowed : [],
]);
?>
<div class="mb-4 filter-block filter-block--categories">
    <label class="form-label fw-bold d-block"><?php esc_html_e('Categories', 'wc-smart-filters'); ?></label>
    <?php if (!is_wp_error($cats) && $cats): ?>
        <?php foreach ($cats as $cat): ?>
            <div class="form-check">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="cats[]"
                    value="<?php echo esc_attr($cat->slug); ?>"
                    id="cat-<?php echo esc_attr($cat->term_id); ?>"
                    <?php checked(in_array($cat->slug, $cats_sel, true)); ?>
                >
                <label class="form-check-label" for="cat-<?php echo esc_attr($cat->term_id); ?>">
                    <?php echo esc_html($cat->name); ?>
                    <span class="badge text-bg-light"><?php echo number_format_i18n($cat->count); ?></span>
                </label>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted small"><?php esc_html_e('No product categories found.', 'wc-smart-filters'); ?></p>
    <?php endif; ?>
</div>

<?php
// 2. Brands block (same markup/style as categories)
if ( get_option('wcsf_show_brand_filter') ) {
    $brands_sel = isset($_GET['brands']) ? (array) $_GET['brands'] : [];
    $allowed_brands = (array) get_option('wcsf_brand_filter_list', []);
    $brands = get_terms([
        'taxonomy'   => 'product_brand',
        'hide_empty' => true,
        'include'    => $allowed_brands ? $allowed_brands : [],
    ]);
    ?>
    <div class="mb-4 filter-block filter-block--brands">
        <label class="form-label fw-bold d-block"><?php esc_html_e('Brands', 'wc-smart-filters'); ?></label>
        <?php if (!is_wp_error($brands) && $brands): ?>
            <?php foreach ($brands as $brand): ?>
                <div class="form-check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="brands[]"
                        value="<?php echo esc_attr($brand->slug); ?>"
                        id="brand-<?php echo esc_attr($brand->term_id); ?>"
                        <?php checked(in_array($brand->slug, $brands_sel, true)); ?>
                    >
                    <label class="form-check-label" for="brand-<?php echo esc_attr($brand->term_id); ?>">
                        <?php echo esc_html($brand->name); ?>
                        <span class="badge text-bg-light"><?php echo number_format_i18n($brand->count); ?></span>
                    </label>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted small"><?php esc_html_e('No brands found.', 'wc-smart-filters'); ?></p>
        <?php endif; ?>
    </div>
<?php } ?>


<?php if ( get_option('wcsf_show_tag_filter') && taxonomy_exists('product_tag')  ): 
    $tags_sel      = isset($_GET['tags']) ? (array) $_GET['tags'] : [];
    $allowed_tags  = (array) get_option('wcsf_tag_filter_list', []);
    $tags          = get_terms([
      'taxonomy'   => 'product_tag',
      'hide_empty' => true,
      'include'    => $allowed_tags ?: [],
    ]);
  ?>
    <div class="mb-4 filter-block filter-block--tags">
      <label class="form-label fw-bold d-block">
        <?php esc_html_e('Tags', 'wc-smart-filters'); ?>
      </label>
      <?php if (!is_wp_error($tags) && $tags): ?>
        <?php foreach($tags as $tag): ?>
          <div class="form-check">
            <input 
              class="form-check-input"
              type="checkbox"
              name="tags[]"
              value="<?php echo esc_attr($tag->slug); ?>"
              id="tag-<?php echo esc_attr($tag->term_id); ?>"
              <?php checked(in_array($tag->slug,$tags_sel,true)); ?>
            >
            <label class="form-check-label" for="tag-<?php echo esc_attr($tag->term_id); ?>">
              <?php echo esc_html($tag->name); ?>
              <span class="badge text-bg-light">
                <?php echo number_format_i18n($tag->count); ?>
              </span>
            </label>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-muted small"><?php esc_html_e('No tags found.', 'wc-smart-filters'); ?></p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php
// 3. Rating filter
if ( get_option('wcsf_show_rating_filter') ) :

    // ← DEFINE A DEFAULT VALUE FOR YOUR SELECTED RATING
    $sel_rating = isset( $_GET['min_rating'] ) 
        ? intval( $_GET['min_rating'] ) 
        : 0;
?>
  <div class="filter-block filter-block--rating">
    <label class="form-label fw-bold d-block">
      <?php esc_html_e('Minimum Rating', 'wc-smart-filters'); ?>
    </label>
    <select name="min_rating" class="form-select">
      <option value=""><?php esc_html_e('Any rating', 'wc-smart-filters'); ?></option>
      <?php for ( $i = 5; $i >= 1; $i-- ) : ?>
        <option value="<?php echo $i; ?>" <?php selected( $sel_rating, $i ); ?>>
          <?php printf( _n('%d star & up','%d stars & up', $i, 'wc-smart-filters'), $i ); ?>
        </option>
      <?php endfor; ?>
    </select>
  </div>
<?php
endif;
?>

  <?php if ( get_option('wcsf_show_search_box') ) :
    $search_val = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
  $search_val = isset($_GET['search']) ? sanitize_text_field( $_GET['search'] ) : '';
?>
  <div class="mb-4 filter-block filter-block--search">
    <input
      type="text"
      name="search"
      class="form-control"
      placeholder="<?php esc_attr_e('Search product name or SKU…','wc-smart-filters'); ?>"
      value="<?php echo esc_attr( $search_val ); ?>"
    >
  </div>
<?php endif; ?>

<?php if ( get_option('wcsf_show_price_filter') ) :
    $min = isset($_GET['min_price']) ? floatval($_GET['min_price']) : floatval(get_option('wcsf_price_filter_min', 0));
    $max = isset($_GET['max_price']) ? floatval($_GET['max_price']) : floatval(get_option('wcsf_price_filter_max', 1000));
  $min = isset($_GET['min_price'])
    ? floatval($_GET['min_price'])
    : floatval(get_option('wcsf_price_filter_min',0));
  $max = isset($_GET['max_price'])
    ? floatval($_GET['max_price'])
    : floatval(get_option('wcsf_price_filter_max',1000));
?>
  <div class="mb-4 filter-block filter-block--price">
    <label class="form-label fw-bold d-block">
      <?php esc_html_e('Price range', 'wc-smart-filters'); ?>
    </label>

    <!-- ← Add these two inputs above the slider -->
    <div class="d-flex mb-2">
      <input
        type="number"
        id="min_price_input"
        class="form-control me-2"
        placeholder="<?php esc_attr_e('Min', 'wc-smart-filters'); ?>"
        value="<?php echo esc_attr($min); ?>"
        step="0.01"
        min="0"
      />
      <input
        type="number"
        id="max_price_input"
        class="form-control"
        placeholder="<?php esc_attr_e('Max', 'wc-smart-filters'); ?>"
        value="<?php echo esc_attr($max); ?>"
        step="0.01"
        min="0"
      />
    </div>

    <!-- your existing slider -->
    <div id="wcsf-price-slider" style="margin-bottom: .5rem;"></div>

    <!-- optional label, if you still want it -->
    <div class="mb-2">
      <span id="wcsf-price-label">
        <?php echo wc_price($min) . ' – ' . wc_price($max); ?>
      </span>
    </div>

    <!-- keep your hidden fields for the AJAX -->
    <input type="hidden" name="min_price" id="min_price" value="<?php echo esc_attr($min); ?>">
    <input type="hidden" name="max_price" id="max_price" value="<?php echo esc_attr($max); ?>">
  </div>
<?php endif; ?>

</form>
