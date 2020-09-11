/**
 * @file
 */

(function ($, Drupal, CKEDITOR) {
  Drupal.behaviors.ucsfpharmacy_drimage = {
    attach: function attach(context) {
      CKEDITOR.on('instanceCreated', function(event) {
        var editor = event.editor;
        editor.on('dataReady', function() {
          if(editor.document) {
            Drupal.drimage.init(editor.document.$);
          }
        });
        editor.on('unlockSnapshot', function() {
          if(editor.document) {
            Drupal.drimage.init(editor.document.$);
          }
        });
      });
    }
  };
})(jQuery, Drupal, CKEDITOR);
