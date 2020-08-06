(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.panolens = {
    attach: function (context, settings) {
      console.log('panolens attach');

      //const panorama = new PANOLENS.ImagePanorama('asset/equirectangular.jpg');
      //const viewer = new PANOLENS.Viewer();
      //viewer.add(panorama);

      $('input.myCustomBehavior', context).once('myCustomBehavior').each(function () {
        // Apply the myCustomBehaviour effect to the elements only once.
      });
    }
  };

})(jQuery, Drupal);
