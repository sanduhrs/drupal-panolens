<?php

namespace Drupal\panolens\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldFormatter\FileVideoFormatter;

/**
 * Plugin implementation of the 'Video Panorama' formatter.
 *
 * @FieldFormatter(
 *   id = "panolens_video_panorama",
 *   label = @Translation("Video Panorama"),
 *   description = @Translation("Display the file using the Panolens.js library."),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class VideoPanoramaFormatter extends FileVideoFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $source_files = $this->getSourceFiles($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($source_files)) {
      return $elements;
    }

    foreach ($source_files as $delta => $files) {
      $file = array_pop($files)['file'];
      $cache_contexts = [];

      $file_uri = $file->getFileUri();
      // @todo Wrap in file_url_transform_relative(). This is currently
      // impossible. As a work-around, we currently add the 'url.site' cache
      // context to ensure different file URLs are generated for different
      // sites in a multisite setup, including HTTP and HTTPS versions of the
      // same site. Fix in https://www.drupal.org/node/2646744.
      $url = Url::fromUri(file_create_url($file_uri));
      $cache_contexts[] = 'url.site';

      $cache_tags = $file->getCacheTags();

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $embed_url = Url::fromRoute(
        'panolens.embed',
        [
          'url' => $url->toString(),
          'format' => 'video-panorama',
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
