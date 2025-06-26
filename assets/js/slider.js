jQuery(function($){
  var $slider = $('#wcsf-price-slider');
  if ( !$slider.length ) return;

  // cache your inputs
  var $minHid = $('#min_price'),
      $maxHid = $('#max_price'),
      $minInp = $('#min_price_input'),
      $maxInp = $('#max_price_input'),
      $label  = $('#wcsf-price-label');

  $slider.slider({
    range: true,
    min: wcsfPrice.min,
    max: wcsfPrice.max,
    values: [
      Number($minHid.val()),
      Number($maxHid.val())
    ],
    slide: function(event, ui){
      // sync hidden + text inputs
      $minHid.val(ui.values[0]);
      $maxHid.val(ui.values[1]);
      $minInp.val(ui.values[0]);
      $maxInp.val(ui.values[1]);
      if ($label.length) {
        $label.text(ui.values[0] + ' – ' + ui.values[1]);
      }
    },
    stop: function(event, ui){
      // trigger your existing change‐handler (fires the AJAX)
      $maxHid.trigger('change');
    }
  });

  // if user types in one of the text boxes, update the slider & hidden fields
  $minInp.add($maxInp).on('change', function(){
    var min = parseFloat($minInp.val()) || wcsfPrice.min;
    var max = parseFloat($maxInp.val()) || wcsfPrice.max;

    // clamp
    min = Math.max(wcsfPrice.min, Math.min(min, wcsfPrice.max));
    max = Math.max(wcsfPrice.min, Math.min(max, wcsfPrice.max));
    if (min > max) min = max;

    // move slider
    $slider.slider('values', [min, max]);

    // sync hidden & label
    $minHid.val(min);
    $maxHid.val(max);
    if ($label.length) {
      $label.text(min + ' – ' + max);
    }

    // fire AJAX
    $maxHid.trigger('change');
  });
});
