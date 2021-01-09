(function($) {
  // Argument passed from InvokeCommand in ucsfpharmacy_meta_scraper().
  $.fn.metaScraperAjaxCallback = function(data) {

    if(data['og:title'] != undefined) {
      $('input[name="title[0][value]"]').val(data['og:title']);
    }

    if(data['og:image'] != undefined && $('input[name="field_article_image[0][filefield_remote][url]"').length) {
      $('input[name="field_article_image[0][filefield_remote][url]"').val(data['og:image']);

      if($('input[name="field_article_image[0][filefield_remote][url]"').length) {
        // simulate pressing "transfer" button to insert image into field
        $('input[name="field_article_image_0_transfer"]').mousedown();

        if(data['og:image:alt'] != undefined) {
          // set up mutation observer to detect appearance of image alt field
          var observer = new MutationObserver(function (mutations, me) {
            var canvas = document.getElementById('edit-field-article-image-wrapper');
            if (canvas) {
              $('input[name="field_article_image[0][alt]"]').val(data['og:image:alt']);
              me.disconnect(); // stop observing
              return;
            }
          });
          // start observing
          observer.observe(document, {
            childList: true,
            subtree: true
          });
        }
      }
    }


    if(data['article:published_time'] != undefined) {
      var date = new Date(data['article:published_time']);
      var date_formatted = date.getFullYear() + '-' + ('0' + (date.getMonth()+1)).slice(-2) + '-' + ('0' + date.getDate()).slice(-2);
      $('input[name="field_article_date[0][value][date]"]').val(date_formatted);
    }

    if(data['og:description'] != undefined) {
      $('textarea[name="field_article_body[0][value]"]').val(data['og:description']);
    }

    if(data['article:author'] != undefined) {
      $('input[name="field_article_author[0][value]"]').val(data['article:author']);
    }

    if(data['og:site_name'] != undefined) {
      $('input[name="field_article_source[0][value]"]').val(data['og:site_name']);
    }
  };
})(jQuery);
