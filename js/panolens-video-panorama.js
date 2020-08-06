(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.panolensVideoPanorama = {
    attach: function (context, settings) {
      console.log(settings.panolens.url);

      $('html, body')
        .css('height', '100%')
        .css('margin', '0');
      $('body', context).once('panolensVideoPanorama').each(function () {
        const panorama = new PANOLENS.VideoPanorama(settings.panolens.url);
        const viewer = new PANOLENS.Viewer();
        viewer.add(panorama);
      });
    }
  };

})(jQuery, Drupal);
