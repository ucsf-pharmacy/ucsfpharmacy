/**
 * @file
 */

(function ($, Drupal, CKEDITOR) {
  Drupal.behaviors.drimage2_ckeditor = {
    attach: function attach(context) {
      CKEDITOR.on('instanceReady', function(event) {
        var editor = event.editor;
        editor.on('unlockSnapshot', function() {
          setTimeout(function() {
            Drupal.drimage2.init(editor.document.$);
          }, 200);
        });
      });
    }
  };
})(jQuery, Drupal, CKEDITOR);
