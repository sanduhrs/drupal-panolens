<?php

namespace Drupal\panolens\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'Image Panorama' formatter.
 *
 * @FieldFormatter(
 *   id = "panolens_image_panorama",
 *   label = @Translation("Image Panorama"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImagePanoramaFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      $cache_contexts = [];

      $file_uri = $file->getFileUri();
      // @todo Wrap in file_url_transform_relative(). This is currently
      // impossible. As a work-around, we currently add the 'url.site' cache
      // context to ensure different file URLs are generated for different
      // sites in a multisite setup, including HTTP and HTTPS versions of the
      // same site. Fix in https://www.drupal.org/node/2646744.
      $url = Url::fromUri(file_create_url($file_uri));
      $cache_contexts[] = 'url.site';

      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $embed_url = Url::fromRoute(
        'panolens.embed',
        [
          'url' => str_replace(['http:', "https:"], '', $url->toString()),
          'format' => 'image-panorama',
        ]
      );

      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#item_attributes' => $item_attributes,
        '#attributes' => [
          'src' => $embed_url->toString(),
          'scrolling' => 'no',
          'class' => 'panolens--embed-iframe',
        ],
        '#attached' => [
          'library' => [
            'panolens/embed',
          ],
        ],
        '#cache' => [
          'tags' => $cache_tags,
          'contexts' => $cache_contexts,
        ],
      ];
    }

    return $elements;
  }

}
