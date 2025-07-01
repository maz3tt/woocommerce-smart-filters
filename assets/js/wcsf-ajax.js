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
      var name = $(this).data('name');
      var val  = $(this).data('value');
  
      if ( name === 'price' ) {
          // Clear both hidden and visible price inputs
          $('#min_price, #max_price').val('');
          $('#min_price_input, #max_price_input').val('');
      } else {
          // Uncheck any matching checkbox
          $(`[name="${name}"][value="${val}"]`).prop('checked', false);
  
          // Clear single‐value filters
          if ( name === 'min_rating' ) {
              $('select[name="min_rating"]').val('');
          }
          if ( name === 'search' ) {
              $('input[name="search"]').val('');
          }
      }
  
      // Reload products via AJAX
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
    
      // 1) strip existing /page/X/ and old params…
      url.pathname = url.pathname.replace(/\/page\/\d+(?=\/|$)/g, '');
      ['cats[]','brands[]','tags[]','min_rating','min_price','max_price','search','product-page']
        .forEach(p => url.searchParams.delete(p));
    
      // 2) re‐append cats, brands, tags as before…
      $('.wc-filter-form input[name="cats[]"]:checked').each(function(){
        url.searchParams.append('cats[]', this.value);
      });
      $('.wc-filter-form input[name="brands[]"]:checked').each(function(){
        url.searchParams.append('brands[]', this.value);
      });
      $('.wc-filter-form input[name="tags[]"]:checked').each(function(){
        url.searchParams.append('tags[]', this.value);
      });
    
      // 3) rating, price, search…
      const rating = $('.wc-filter-form select[name="min_rating"]').val();
      if (rating) url.searchParams.set('min_rating', rating);
    
      if ( $('.filter-block--price').length ) {
        const minP = parseFloat( $('#min_price').val() ),
              maxP = parseFloat( $('#max_price').val() ),
              defMin = parseFloat( wcsfPrice.min ),
              defMax = parseFloat( wcsfPrice.max );
      
        // only set if at least one end of the range has changed
        if ( minP !== defMin || maxP !== defMax ) {
          url.searchParams.set('min_price', minP);
          url.searchParams.set('max_price', maxP);
        }
      }
    
      const search = $('.wc-filter-form input[name="search"]').val().trim();
      if (search) url.searchParams.set('search', search);
    
      // 4) **Attributes** — loop through the dynamic list
      // ——— Attributes ———
      if ( Array.isArray(wcsfAjax.attributeTaxonomies) ) {
        wcsfAjax.attributeTaxonomies.forEach(function(tax){
          // clear any old values
          url.searchParams.delete(tax + '[]');
          // add each checked attribute term
          $(`.wc-filter-form input[name="${tax}[]"]:checked`).each(function(){
            url.searchParams.append(tax + '[]', this.value);
          });
        });
      }

    
      // 5) pagination (/page/X/ or ?product-page=)
      if (page > 1) {
        if (/\/shop\/?$/.test(url.pathname)) {
          url.pathname = url.pathname.replace(/\/$/, '') + `/page/${page}/`;
        } else {
          url.searchParams.set('product-page', page);
        }
      }
    
      return url.href;
    }
    
    
    

    // Render filter tags (now shows brands as well!) -----------------
    function renderFilterTags() {

      const urlParams = new URLSearchParams(window.location.search);

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
    
      
        // ——— Price tag ———
        // ONLY render if the URL actually has a min_price or max_price param
        if ( $('.filter-block--price').length ) {
          const defMin = parseFloat( wcsfPrice.min ),
                defMax = parseFloat( wcsfPrice.max ),
                minP   = urlParams.has('min_price')
                           ? parseFloat( urlParams.get('min_price') )
                           : defMin,
                maxP   = urlParams.has('max_price')
                           ? parseFloat( urlParams.get('max_price') )
                           : defMax;
        
          // only render if the user actually changed one of them
          if ( ( urlParams.has('min_price') && minP !== defMin )
            || ( urlParams.has('max_price') && maxP !== defMax ) ) {
        
            tags.push(`
              <span class="filter-tag badge bg-success me-2">
                ${minP} – ${maxP}
                <span data-name="price" class="remove-tag">&times;</span>
              </span>
            `);
          }
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

        if ( Array.isArray(wcsfAjax.attributeTaxonomies) ) {
          wcsfAjax.attributeTaxonomies.forEach(function(tax){
            $(`.wc-filter-form input[name="${tax}[]"]:checked`).each(function(){
              // grab the human label text
              const label = $(this)
                .closest('div')
                .find('label')
                .clone()
                .children().remove().end()
                .text().trim();
        
              tags.push(`
                <span class="filter-tag badge bg-secondary me-2">
                  ${label}
                  <span 
                    data-name="${tax}[]" 
                    data-value="${this.value}" 
                    class="remove-tag">&times;
                  </span>
                </span>
              `);
            });
          });
        }
    
        $tagsContainer.html(tags.join(''));
    }
    

    // Sync filter tags from URL (supports brands & cats) -------------
   // function renderFilterTagsFromURL() {
   //   const urlParams = new URLSearchParams(window.location.search);
  
    
  
   //   const minP = urlParams.get('min_price');
   //   const maxP = urlParams.get('max_price');
   //   if (minP !== null && maxP !== null) {
   //      $('#min_price').val(minP);
   //      $('#max_price').val(maxP);
   //      $('input[name="min_price"]').val(minP);
   //      $('input[name="max_price"]').val(maxP);
  
          // **only** update the slider if it’s been initialized**
   //      const $slider = $('#wcsf-price-slider');
   //      if ( $slider.length && $slider.hasClass('ui-slider') ) {
   //          $slider.slider('values', [ parseFloat(minP), parseFloat(maxP) ]);
   //       }
  
          // update the label every time
    //      $('#wcsf-price-label').text(`${minP} – ${maxP}`);
    //  }
  
     // renderFilterTags();
  //}

  function renderFilterTagsFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
  
    // 1) Reset all filters in the form
    $('.wc-filter-form')[0]?.reset();
    // (if you have sliders, reset them manually too)
    if ( $('#wcsf-price-slider').hasClass('ui-slider') ) {
      $('#wcsf-price-slider')
        .slider('values', [ wcsfPrice.min, wcsfPrice.max ]);
      $('#wcsf-price-label').text(`${wcsfPrice.min} – ${wcsfPrice.max}`);
    }
  
    // 2) Re-check checkboxes & re-fill single-value controls
    urlParams.getAll('cats[]').forEach(v => {
      $(`input[name="cats[]"][value="${v}"]`).prop('checked', true);
    });
    urlParams.getAll('brands[]').forEach(v => {
      $(`input[name="brands[]"][value="${v}"]`).prop('checked', true);
    });
    urlParams.getAll('tags[]').forEach(v => {
      $(`input[name="tags[]"][value="${v}"]`).prop('checked', true);
    });
    if ( urlParams.has('min_rating') ) {
      $('select[name="min_rating"]').val(urlParams.get('min_rating'));
    }
    if ( urlParams.has('search') ) {
      $('input[name="search"]').val(urlParams.get('search'));
    }
  
    // 3) Only set price inputs *if* both min_price & max_price
    //    are present and different from the defaults
    const minParam = urlParams.get('min_price');
    const maxParam = urlParams.get('max_price');
    if ( minParam !== null && maxParam !== null ) {
      const minP = parseFloat(minParam);
      const maxP = parseFloat(maxParam);
      const defMin = parseFloat(wcsfPrice.min);
      const defMax = parseFloat(wcsfPrice.max);
  
      if (
           ! isNaN(minP) && ! isNaN(maxP)
        && (minP !== defMin || maxP !== defMax)
      ) {
        $('#min_price_input, #min_price').val(minP);
        $('#max_price_input, #max_price').val(maxP);
        $('#max_price').val(maxP);
        if ( $('#wcsf-price-slider').hasClass('ui-slider') ) {
          $('#wcsf-price-slider').slider('values', [ minP, maxP ]);
        }
        $('#wcsf-price-label').text(`${minP} – ${maxP}`);
      }
    }
  
    // 4) Finally draw the little badge-tags
    renderFilterTags();
  }
  

// call on page load
//jQuery(function($){
 // renderFilterTagsFromURL();
//});

  


  
    

});
