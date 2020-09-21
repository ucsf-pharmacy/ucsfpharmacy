/**
 * @file
 */

(function ($, Drupal, CKEDITOR) {
  Drupal.behaviors.drimage2_ckeditor = {
    attach: function attach(context) {
      CKEDITOR.on('instanceCreated', function(event) {
        var editor = event.editor;
        editor.on('unlockSnapshot', function() {
          if(editor.document) {
            setTimeout(function() {
              Drupal.drimage2.init(editor.document.$);
            }, 500);
          }
        });
      });
    }
  };
})(jQuery, Drupal, CKEDITOR);
