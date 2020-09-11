/**
 * @file
 */

(function ($, Drupal, CKEDITOR) {
  Drupal.behaviors.ucsfpharmacy_drimage = {
    attach: function attach(context) {
    	for(var i in CKEDITOR.instances) {
	    	CKEDITOR.instances[i].on("instanceReady", function() {
	      	setTimeout(function() {
		        Drupal.drimage.init(CKEDITOR.instances[i].document.$);
		      }, 500);
	    	});
	    	CKEDITOR.instances[i].on("focus", function() {
	      	setTimeout(function() {
		        Drupal.drimage.init(CKEDITOR.instances[i].document.$);
		      }, 500);
	    	});
	    }
	  }
  };
})(jQuery, Drupal, CKEDITOR);