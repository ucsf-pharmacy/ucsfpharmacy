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
            setTimeout(Drupal.drimage2.init(editor.document.$), 300);
          }
        });
      });
    }
  };
})(jQuery, Drupal, CKEDITOR);
