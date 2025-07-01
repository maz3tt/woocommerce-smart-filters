jQuery(function($){
  // Open / close offcanvas
  $('#wcsf-mobile-filters-btn').on('click', () => {
    $('body').addClass('wcsf-offcanvas-open');
  });
  $('.wcsf-offcanvas-overlay, .wcsf-offcanvas-close').on('click', () => {
    $('body').removeClass('wcsf-offcanvas-open');
  });

  // Clear all filters
  $('#wcsf-clear-filters').on('click', () => {
    const form = $('.wc-filter-form')[0];
    if ( form ) form.reset();    // reset all inputs
    fetchProducts(1);            // trigger AJAX reload page 1
  });

  // Apply inside offcanvas
  $('#wcsf-apply-filters').on('click', (e) => {
    e.preventDefault();
    $('body').removeClass('wcsf-offcanvas-open');
    fetchProducts(1);
  });
});
