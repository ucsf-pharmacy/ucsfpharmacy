(function (document, Drupal) {

  'use strict';

  Drupal.drimage2 = {};

  Drupal.drimage2.findDelayParent = function (el) {
    if (el.parentNode === null) {
      return null;
    }
    if (el.parentNode.classList && el.parentNode.classList.contains('js-delay-drimage2')) {
      return el.parentNode;
    }
    return Drupal.drimage2.findDelayParent(el.parentNode);
  };

  Drupal.drimage2.resize = function (size, r, d) {
    if (size[d] === 0) {
      return size;
    }

    // Clone values into new array.
    var new_size = {
      0: size[0],
      1: size[1]
    };
    new_size[d] = r;

    var inverse_d = Math.abs(d - 1);
    if (size[inverse_d] === 0) {
      return new_size;
    }
    new_size[inverse_d] = Math.round(new_size[inverse_d] * (new_size[d] / size[d]));

    return new_size;
  };

  Drupal.drimage2.fetchData = function (el) {
    var data = JSON.parse(el.getAttribute('data-drimage2'));
    data.upscale = parseInt(data.upscale);
    data.downscale = parseInt(data.downscale);
    data.threshold = parseInt(data.threshold);
    return data;
  };

  Drupal.drimage2.size = function (el) {
    if (el.offsetWidth === 0) {
      return { 0: 0, 1: 0 };
    }

    var data = Drupal.drimage2.fetchData(el);
    var size = {
      0: el.offsetWidth,
      1: 0
    };

    // Get the screen multiplier to deliver higher quality images.
    var multiplier = 1;
    if (data.multiplier === 1) {
      multiplier = Number(window.devicePixelRatio);
      if (isNaN(multiplier) === true || multiplier <= 0) {
        multiplier = 1;
      }
    }
    size[0] = Math.round(size[0] * multiplier);
    size[1] = Math.round(size[1] * multiplier);

    // Make sure the requested image isn't to small.
    if (size[0] < data.upscale) {
      size = Drupal.drimage2.resize(size, data.upscale, 0);
    }

    // Reduce all widths to a multiplier of the threshold, starting at the
    // minimal upscaling.
    var w = size[0] - data.upscale;

    var r = (Math.ceil(w / data.threshold) * data.threshold) + data.upscale;
    // When the multiplier is > 1 we can use a slightly smaller image style as
    // long as the resulting width is at least the original un-multiplied width.
    // if (multiplier > 1) {
    //   var r_alt = (Math.floor(w / data.threshold) * data.threshold) + data.upscale;
    //   if (r_alt >= size[0] / multiplier) {
    //     r = r_alt;
    //   }
    // }
    size = Drupal.drimage2.resize(size, r, 0);

    // Downscale the image if it is to larger.
    if (size[0] > data.downscale) {
      size = Drupal.drimage2.resize(size, data.downscale, 0);
    }

    return size;
  };

  Drupal.drimage2.init = function (context) {
    if (typeof context === 'undefined') {
      context = document;
    }
    var el = context.querySelectorAll('.drimage2');
    if (el.length > 0) {
      for (var i = 0; i < el.length; i++) {
        var data = Drupal.drimage2.fetchData(el[i]);

        Drupal.drimage2.renderEl(el[i]);
      }
    }
  };

  Drupal.drimage2.renderEl = function (el) {
    var delay = Drupal.drimage2.findDelayParent(el);
    if (delay === null) {
      var rect = el.getBoundingClientRect();
      var data = Drupal.drimage2.fetchData(el);
      if ((rect.top + data.lazy_offset >= 0 && rect.top - data.lazy_offset <= (window.innerHeight || document.documentElement.clientHeight))
        || (rect.bottom + data.lazy_offset >= 0 && rect.bottom - data.lazy_offset <= (window.innerHeight || document.documentElement.clientHeight))) {
        if (isNaN(data.fid) === false && data.fid % 1 === 0 && Number(data.fid) > 0) {
          var size = Drupal.drimage2.size(el);
          if (size[0] > 0) {
            var downloadingImage = new Image();
            downloadingImage.onload = function () {
              var img = el.querySelectorAll('img');
              if (img.length > 0) {
                img[0].src = this.src;
              }
            };
            downloadingImage.src = '/drimage2/' + size[0] + '/' + size[1] + '/' + data.crop_type + '/' + data.fid + '/' + encodeURIComponent(data.filename);
          }
        }
      }
    }
  };

  Drupal.behaviors.drimage2 = {
    attach: function (context) {
      Drupal.drimage2.init(context);
      var timer;
      addEventListener('resize', function () {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage2.init, 100);
      });
      addEventListener('scroll', function () {
        Drupal.drimage2.init(document);
      });
    }
  };

})(document, Drupal);
