/**
 * @file
 */

(function ($, Drupal, CKEDITOR) {
  Drupal.behaviors.ucsfpharmacy_drimage = {
    attach: function attach(context) {
      CKEDITOR.on('instanceCreated', function(event) {
        var editor = event.editor;
        editor.on('unlockSnapshot', function() {
          if(editor.document) {
            setTimeout(Drupal.drimage.init(editor.document.$), 200);
          }
        });
      });
    }
  };
})(jQuery, Drupal, CKEDITOR);
