jQuery(function ($) {

    // Initial load: Render tags from URL
    renderFilterTagsFromURL();

    // 1. Filter changes ----------------------------------------------
    $(document).on('change', '.wc-filter-form :input', () => fetchProducts(1));

    // 2. Pagination clicks -------------------------------------------
    $(document).on('click', '#wcsf-products .woocommerce-pagination a', function (e) {
        e.preventDefault();
        const href = $(this).attr('href') || '';
        let page = parseInt(href.match(/(?:page\/|product-page=)(\d+)/i)?.[1]) || 1;
        fetchProducts(page);
    });

    // 3. Remove filter tag (works for cats and brands) ---------------
    $(document).on('click', '.filter-tag .remove-tag', function(){
        const name = $(this).data('name');
        const val  = $(this).data('value');
        // uncheck checkboxes or clear inputs/selects by name…
        $(`[name="${name}"][value="${val}"]`).prop('checked', false);
        // for single-value inputs or selects:
        if (name === 'min_rating')    $(`select[name="min_rating"]`).val('');
        if (name === 'search')        $(`input[name="search"]`).val('');
        if (name === 'min_price')     $('#min_price').val('');
        if (name === 'max_price')     $('#max_price').val('');
        fetchProducts(1);
    });

    // fetchProducts Function -----------------------------------------
    function fetchProducts(page) {
        const data = $('.wc-filter-form').serializeArray();
        data.push({ name: 'action', value: 'wcsf_filter' });
        data.push({ name: 'security', value: wcsfAjax.nonce });
        data.push({ name: 'paged', value: page });
        data.push({ name: 'current_url', value: window.location.href });

        $('#wcsf-products').fadeTo(120, .3);

        $.post(wcsfAjax.url, data, function (res) {
            if (!res.success) return;

            $('#wcsf-products').html(res.data.html).fadeTo(120, 1);
            window.scrollTo({ top: $('#wcsf-products').offset().top - 120, behavior: 'smooth' });

            history.pushState(null, '', buildUrlForPage(page));
            renderFilterTags();
        });
    }

    // buildUrlForPage Function (now supports brands too!) ------------
    function buildUrlForPage(page) {
        const url = new URL(window.location.href);
    
        // 1. clean existing params
        url.searchParams.delete('cats[]');
        url.searchParams.delete('brands[]');
        url.searchParams.delete('tags[]');
        url.searchParams.delete('min_rating');
        url.searchParams.delete('min_price');
        url.searchParams.delete('max_price');
        url.searchParams.delete('search');
        url.searchParams.delete('product-page');
    
        // 2. re-append array params
        $('.wc-filter-form input[name="cats[]"]:checked').each(function(){
          url.searchParams.append('cats[]', $(this).val());
        });
        $('.wc-filter-form input[name="brands[]"]:checked').each(function(){
          url.searchParams.append('brands[]', $(this).val());
        });
        $('.wc-filter-form input[name="tags[]"]:checked').each(function(){
          url.searchParams.append('tags[]', $(this).val());
        });
    
        // 3. single-value params
        const minRating = $('.wc-filter-form select[name="min_rating"]').val();
        if (minRating) url.searchParams.set('min_rating', minRating);
    
        const minPrice = $('#min_price').val();
        const maxPrice = $('#max_price').val();
        if (minPrice) url.searchParams.set('min_price', minPrice);
        if (maxPrice) url.searchParams.set('max_price', maxPrice);
    
        const search = $('.wc-filter-form input[name="search"]').val().trim();
        if (search) url.searchParams.set('search', search);
    
        // 4. pagination
        if (url.pathname.endsWith('/shop/')) {
          url.pathname = url.pathname.replace(/\/page\/\d+\/$/, '/');
          if (page > 1) url.pathname += `page/${page}/`;
        } else {
          if (page > 1) url.searchParams.set('product-page', page);
        }
    
        return url.toString();
    }

    // Render filter tags (now shows brands as well!) -----------------
    function renderFilterTags() {
        let $tagsContainer = $('#filter-tags');
        if (!$tagsContainer.length) {
            $('#wcsf-products').before('<div id="filter-tags" class="mb-3"></div>');
            $tagsContainer = $('#filter-tags');
        }
    
        const tags = [];
    
        // ——— Category tags ———
        $('.wc-filter-form input[name="cats[]"]:checked').each(function() {
            const name = $(this).closest('div').find('label').clone()
                             .children().remove().end()
                             .text().trim();
            tags.push(`
              <span class="filter-tag badge bg-secondary me-2">
                ${name}
                <span data-name="cats[]" data-value="${$(this).val()}"
                      class="remove-tag">&times;</span>
              </span>`);
        });
    
        // ——— Brand tags ———
        $('.wc-filter-form input[name="brands[]"]:checked').each(function() {
            const name = $(this).closest('div').find('label').clone()
                             .children().remove().end()
                             .text().trim();
            tags.push(`
              <span class="filter-tag badge bg-info me-2">
                ${name}
                <span data-name="brands[]" data-value="${$(this).val()}"
                      class="remove-tag">&times;</span>
              </span>`);
        });
    
        // ——— Product Tag tags ———
        $('.wc-filter-form input[name="tags[]"]:checked').each(function() {
            const name = $(this).closest('div').find('label').clone()
                             .children().remove().end()
                             .text().trim();
            tags.push(`
              <span class="filter-tag badge bg-warning me-2">
                ${name}
                <span data-name="tags[]" data-value="${$(this).val()}"
                      class="remove-tag">&times;</span>
              </span>`);
        });
    
        // ——— Rating tag ———
        const minRating = $('.wc-filter-form select[name="min_rating"]').val();
        if (minRating) {
            tags.push(`
              <span class="filter-tag badge bg-dark text-white me-2">
                ${minRating}★ & up
                <span data-name="min_rating" data-value="${minRating}"
                      class="remove-tag">&times;</span>
              </span>`);
        }
    
        // ——— Price range tag ———
        const minPrice = $('#min_price').val();
        const maxPrice = $('#max_price').val();
        if (minPrice || maxPrice) {
            tags.push(`
              <span class="filter-tag badge bg-success me-2">
                ${minPrice} – ${maxPrice}
                <span data-name="min_price" data-value="${minPrice}"
                      class="remove-tag">&times;</span>
                <span data-name="max_price" data-value="${maxPrice}"
                      class="remove-tag">&times;</span>
              </span>`);
        }
    
        // ——— Search tag ———
        const search = $('.wc-filter-form input[name="search"]').val().trim();
        if (search) {
            tags.push(`
              <span class="filter-tag badge bg-primary text-white me-2">
                “${search}”
                <span data-name="search" data-value="${search}"
                      class="remove-tag">&times;</span>
              </span>`);
        }
    
        $tagsContainer.html(tags.join(''));
    }
    

    // Sync filter tags from URL (supports brands & cats) -------------
    function renderFilterTagsFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
    
        // 1) Uncheck all arrays
        $('input[name="cats[]"],input[name="brands[]"],input[name="tags[]"]')
          .prop('checked', false);
    
        // 2) Clear single-value selects/inputs
        $('select[name="min_rating"]').val('');
        $('input[name="search"]').val('');
        $('#min_price,#max_price').val('');
    
        // 3) Re-apply from URL
        urlParams.getAll('cats[]').forEach(val => {
          $(`input[name="cats[]"][value="${val}"]`).prop('checked', true);
        });
        urlParams.getAll('brands[]').forEach(val => {
          $(`input[name="brands[]"][value="${val}"]`).prop('checked', true);
        });
        urlParams.getAll('tags[]').forEach(val => {
          $(`input[name="tags[]"][value="${val}"]`).prop('checked', true);
        });
    
        const minRating = urlParams.get('min_rating');
        if (minRating) {
          $('select[name="min_rating"]').val(minRating);
        }
    
        const search = urlParams.get('search');
        if (search) {
          $('input[name="search"]').val(search);
        }
    
        const minP = urlParams.get('min_price');
        const maxP = urlParams.get('max_price');
        if (minP !== null && maxP !== null) {
          $('#min_price').val(minP);
          $('#max_price').val(maxP);
    
          // also update your jQuery-UI slider handles:
          $('#wcsf-price-slider').slider('values', [ parseFloat(minP), parseFloat(maxP) ]);
          $('#wcsf-price-label').text(`${minP} – ${maxP}`);
        }
    
        // finally build the visual “filter tags” badges:
        renderFilterTags();
    }
    
    // call on page load
    jQuery(function($){
      renderFilterTagsFromURL();
    });
    

});
