/**
 * @file
 */

(function ($, Drupal, CKEDITOR) {
  Drupal.behaviors.ucsfpharmacy_drimage = {
    attach: function attach(context) {
      CKEDITOR.on('instanceCreated', function(event) {
        var editor = event.editor;
        editor.on('dataReady', function() {
          Drupal.drimage.init(editor.document.$);
        });
        editor.on('unlockSnapshot', function() {
          Drupal.drimage.init(editor.document.$);
        });
      });
    }
  };
})(jQuery, Drupal, CKEDITOR);
