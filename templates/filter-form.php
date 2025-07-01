<?php
/**
 * The filter form template for WC Smart Filters
 */

// 1) Grab the current URL GET selections:
$cats_sel      = isset( $_GET['cats']       ) ? (array) $_GET['cats']       : [];
$brands_sel    = isset( $_GET['brands']     ) ? (array) $_GET['brands']     : [];
$tags_sel      = isset( $_GET['tags']       ) ? (array) $_GET['tags']       : [];
$sel_rating    = isset( $_GET['min_rating'] ) ? intval( $_GET['min_rating'] ) : 0;
$sel_search    = isset( $_GET['search']     ) ? sanitize_text_field( $_GET['search'] ) : '';
$sel_min_price = isset( $_GET['min_price']  ) ? floatval( $_GET['min_price'] )  : floatval( get_option('wcsf_price_filter_min',0) );
$sel_max_price = isset( $_GET['max_price']  ) ? floatval( $_GET['max_price'] )  : floatval( get_option('wcsf_price_filter_max',1000) );

// 2) Build up each block into $blocks[slug] via output buffering:
$blocks = [];

// ─────────────────────────────────────────────────── Categories ─────
ob_start();
$allowed = (array) get_option('wcsf_category_filter_list', []);
$cats = get_terms([
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'include'    => $allowed ?: []
]);
?>
<div class="mb-4 filter-block filter-block--categories">
  <label class="form-label fw-bold d-block"><?php esc_html_e('Categories','wc-smart-filters'); ?></label>
  <?php if (!is_wp_error($cats) && $cats): foreach($cats as $cat): ?>
    <div class="form-check">
      <input 
        class="form-check-input" 
        type="checkbox" 
        name="cats[]" 
        id="cat-<?php echo esc_attr($cat->term_id); ?>" 
        value="<?php echo esc_attr($cat->slug); ?>" 
        <?php checked( in_array($cat->slug, $cats_sel, true) ); ?> 
      />
      <label class="form-check-label" for="cat-<?php echo esc_attr($cat->term_id); ?>">
        <?php echo esc_html($cat->name); ?>
        <span class="badge text-bg-light"><?php echo number_format_i18n( $cat->count ); ?></span>
      </label>
    </div>
  <?php endforeach; else: ?>
    <p class="text-muted small"><?php esc_html_e('No product categories found.','wc-smart-filters'); ?></p>
  <?php endif; ?>
</div>
<?php
$blocks['categories'] = ob_get_clean();


// ────────────────────────────────────────────────────── Brands ─────
if ( get_option('wcsf_show_brand_filter') && taxonomy_exists('product_brand') ) {
  ob_start();
  $allowed = (array) get_option('wcsf_brand_filter_list', []);
  $brands = get_terms([
      'taxonomy'   => 'product_brand',
      'hide_empty' => true,
      'include'    => $allowed ?: []
  ]);
  ?>
  <div class="mb-4 filter-block filter-block--brands">
    <label class="form-label fw-bold d-block"><?php esc_html_e('Brands','wc-smart-filters'); ?></label>
    <?php if (!is_wp_error($brands) && $brands): foreach($brands as $brand): ?>
      <div class="form-check">
        <input 
          class="form-check-input" 
          type="checkbox" 
          name="brands[]" 
          id="brand-<?php echo esc_attr($brand->term_id); ?>" 
          value="<?php echo esc_attr($brand->slug); ?>" 
          <?php checked( in_array($brand->slug, $brands_sel, true) ); ?> 
        />
        <label class="form-check-label" for="brand-<?php echo esc_attr($brand->term_id); ?>">
          <?php echo esc_html($brand->name); ?>
          <span class="badge text-bg-light"><?php echo number_format_i18n( $brand->count ); ?></span>
        </label>
      </div>
    <?php endforeach; else: ?>
      <p class="text-muted small"><?php esc_html_e('No brands found.','wc-smart-filters'); ?></p>
    <?php endif; ?>
  </div>
  <?php
  $blocks['brands'] = ob_get_clean();
}


// ─────────────────────────────────────────────────────── Tags ─────
if ( get_option('wcsf_show_tag_filter') && taxonomy_exists('product_tag') ) {
  ob_start();
  $allowed = (array) get_option('wcsf_tag_filter_list', []);
  $tags = get_terms([
      'taxonomy'   => 'product_tag',
      'hide_empty' => true,
      'include'    => $allowed ?: []
  ]);
  ?>
  <div class="mb-4 filter-block filter-block--tags">
    <label class="form-label fw-bold d-block"><?php esc_html_e('Tags','wc-smart-filters'); ?></label>
    <?php if (!is_wp_error($tags) && $tags): foreach($tags as $tag): ?>
      <div class="form-check">
        <input
          class="form-check-input"
          type="checkbox"
          name="tags[]"
          id="tag-<?php echo esc_attr($tag->term_id); ?>"
          value="<?php echo esc_attr($tag->slug); ?>"
          <?php checked( in_array($tag->slug, $tags_sel, true) ); ?>
        />
        <label class="form-check-label" for="tag-<?php echo esc_attr($tag->term_id); ?>">
          <?php echo esc_html($tag->name); ?>
          <span class="badge text-bg-light"><?php echo number_format_i18n( $tag->count ); ?></span>
        </label>
      </div>
    <?php endforeach; else: ?>
      <p class="text-muted small"><?php esc_html_e('No tags found.','wc-smart-filters'); ?></p>
    <?php endif; ?>
  </div>
  <?php
  $blocks['tags'] = ob_get_clean();
}


// ────────────────────────────────────────────────────── Rating ─────
if ( get_option('wcsf_show_rating_filter') ) {
  ob_start(); ?>
  <div class="mb-4 filter-block filter-block--rating">
    <label class="form-label fw-bold d-block"><?php esc_html_e('Rating','wc-smart-filters'); ?></label>
    <select name="min_rating" class="form-select">
      <option value=""><?php esc_html_e('Any rating','wc-smart-filters'); ?></option>
      <?php for ( $i = 5; $i >= 1; $i-- ): ?>
      <option value="<?php echo $i; ?>" <?php selected( $sel_rating, $i ); ?>>
        <?php printf( _n('%d star & up','%d stars & up',$i,'wc-smart-filters'), $i ); ?>
      </option>
      <?php endfor; ?>
    </select>
  </div>
  <?php
  $blocks['rating'] = ob_get_clean();
}


// ────────────────────────────────────────────────────── Search ─────
if ( get_option('wcsf_show_search_box') ) {
  ob_start(); ?>
  <div class="mb-4 filter-block filter-block--search">
    <label class="form-label fw-bold d-block" for="wcsf-search-input">
      <?php esc_html_e('Search Products','wc-smart-filters'); ?>
    </label>
    <input 
      type="text" 
      id="wcsf-search-input"
      name="search" 
      class="form-control"
      placeholder="<?php esc_attr_e('Enter product name or SKU…','wc-smart-filters'); ?>"
      value="<?php echo esc_attr( $sel_search ); ?>"
    />
  </div>
  <?php
  $blocks['search'] = ob_get_clean();
}


// ─────────────────────────────────────────────────── Price Range ─────
if ( get_option('wcsf_show_price_filter') ) {
  ob_start(); ?>
  <div class="mb-4 filter-block filter-block--price">
    <label class="form-label fw-bold d-block"><?php esc_html_e('Price range','wc-smart-filters'); ?></label>
    <div class="d-flex mb-2">
      <input type="number" id="min_price_input" class="form-control me-2"
        placeholder="<?php esc_attr_e('Min','wc-smart-filters'); ?>"
        value="<?php echo esc_attr( $sel_min_price ); ?>" step="0.01" min="0" />
      <input type="number" id="max_price_input" class="form-control"
        placeholder="<?php esc_attr_e('Max','wc-smart-filters'); ?>"
        value="<?php echo esc_attr( $sel_max_price ); ?>" step="0.01" min="0" />
    </div>
    <div id="wcsf-price-slider" style="margin-bottom:.5rem;"></div>
    <div class="mb-2"><span id="wcsf-price-label">
      <?php echo wc_price( $sel_min_price ) . ' – ' . wc_price( $sel_max_price ); ?>
    </span></div>
    <input type="hidden" name="min_price" id="min_price" value="<?php echo esc_attr( $sel_min_price ); ?>">
    <input type="hidden" name="max_price" id="max_price" value="<?php echo esc_attr( $sel_max_price ); ?>">
  </div>
  <?php
  $blocks['price'] = ob_get_clean();
}


// ─────────────────────────── Dynamic Attributes (pa_*) ─────
if ( get_option('wcsf_show_attribute_filter') ) {
  $attrs = (array) get_option('wcsf_attribute_filter_list', []);
  foreach ( $attrs as $tax ) {
    if ( ! taxonomy_exists($tax) ) {
      continue;
    }
    $terms = get_terms([
      'taxonomy'   => $tax,
      'hide_empty' => true,
    ]);
    if ( is_wp_error($terms) || empty($terms) ) {
      continue;
    }
    // label e.g. from "pa_color" → "Color"
    $label = ucwords(str_replace('_',' ', preg_replace('/^pa_/','',$tax)));
    $sel = isset($_GET[$tax]) ? (array) $_GET[$tax] : [];
    ob_start(); ?>
    <div class="mb-4 filter-block filter-block--<?php echo esc_attr($tax); ?>">
      <label class="form-label fw-bold d-block"><?php echo esc_html($label); ?></label>
      <?php foreach ( $terms as $term ): ?>
        <div class="form-check">
          <input
            class="form-check-input"
            type="checkbox"
            name="<?php echo esc_attr($tax); ?>[]"
            id="<?php echo esc_attr("$tax-{$term->term_id}"); ?>"
            value="<?php echo esc_attr($term->slug); ?>"
            <?php checked( in_array($term->slug, $sel, true) ); ?>
          />
          <label class="form-check-label" for="<?php echo esc_attr("$tax-{$term->term_id}"); ?>">
            <?php echo esc_html($term->name); ?>
            <span class="badge text-bg-light"><?php echo number_format_i18n($term->count); ?></span>
          </label>
        </div>
      <?php endforeach; ?>
    </div>
    <?php
    $blocks[ $tax ] = ob_get_clean();
  }
}


// ──────────────────────────────────────────────────── Sort & Output ─────

// 3) Read the admin’s block‐order positions:
$positions = (array) get_option( 'wcsf_filter_block_order', [] );

// 4) Remove any with position = 0 (hidden):
foreach ( $blocks as $slug => $html ) {
  if ( empty( $positions[ $slug ] ) ) {
    unset( $blocks[ $slug ] );
  }
}

// 5) Sort by their numeric value (smallest first):
uasort( $blocks, function( $a, $b ) use ( $blocks, $positions ) {
  // we need to find their slug keys:
  $keys = array_keys( $blocks );
  $a_key = array_search($a, $blocks, true);
  $b_key = array_search($b, $blocks, true);
  $pa = isset($positions[$a_key]) ? intval($positions[$a_key]) : PHP_INT_MAX;
  $pb = isset($positions[$b_key]) ? intval($positions[$b_key]) : PHP_INT_MAX;
  return $pa <=> $pb;
});

// 6) Finally wrap in the form and echo in order:
?>
<form class="wc-filter-form">
  <?php foreach ( $blocks as $html ): echo $html; endforeach; ?>
</form>
