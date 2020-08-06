<?php

namespace Drupal\panolens\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\media\OEmbed\ResourceException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller which renders an Panolens resource in a bare page.
 */
class PanolensEmbedController implements ContainerInjectionInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The panolens.js formats.
   *
   * @var string[]
   */
  protected $formats = [
    'image-panorama',
    'video-panorama',
  ];

  /**
   * Constructs an OEmbedIframeController instance.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(RendererInterface $renderer, LoggerInterface $logger) {
    $this->renderer = $renderer;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('logger.factory')->get('media')
    );
  }

  /**
   * Renders an oEmbed resource.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Will be thrown if the 'hash' parameter does not match the expected hash
   *   of the 'url' parameter.
   *
   * @todo
   *   Generate and validate a hash see the OEmbedIframeController for more
   *   details at Drupal\media\Controller\OEmbedIframeController.
   */
  public function render(Request $request) {
    $url = $request->query->get('url');
    $format = $request->query->get('format');
    $format = in_array($format, $this->formats) ? $format : array_pop($this->formats);

    // Return a response instead of a render array so that the frame content
    // will not have all the blocks and page elements normally rendered by
    // Drupal.
    $response = new HtmlResponse();
    $response->addCacheableDependency(Url::createFromRequest($request));

    try {
      $placeholder_token = Crypt::randomBytesBase64(55);

      // Render the content in a new render context so that the cacheability
      // metadata of the rendered HTML will be captured correctly.
      $element = [
        '#theme' => 'panolens_embed_iframe',
        '#cache' => [
          // Add the 'rendered' cache tag as this response is not processed by
          // \Drupal\Core\Render\MainContent\HtmlRenderer::renderResponse().
          'tags' => ['rendered'],
        ],
        '#attached' => [
          'html_response_attachment_placeholders' => [
            'scripts_bottom' => '<js-bottom-placeholder token="' . $placeholder_token . '">',
            'styles' => '<css-placeholder token="' . $placeholder_token . '">',
          ],
          'library' => [
            'core/jquery',
            'core/jquery.once',
            'core/drupal',
            'core/drupalSettings',
            'panolens/three.js',
            'panolens/panolens.js',
            'panolens/' . $format,
          ],
          'drupalSettings' => [
            'panolens' => [
              'url' => $url,
              'format' => $format,
            ],
          ],
        ],
        '#placeholder_token' => $placeholder_token,
      ];
      $content = $this->renderer->executeInRenderContext(
        new RenderContext(),
        function () use ($element) {
          return $this->renderer->renderRoot($element);
        }
      );
      $response
        ->setContent($content)
        ->setAttachments($element['#attached'])
        // ->addCacheableDependency($resource)
        ->addCacheableDependency(
          CacheableMetadata::createFromRenderArray($element)
        );
    }
    catch (ResourceException $e) {
      // Prevent the response from being cached.
      $response->setMaxAge(0);

      // The oEmbed system makes heavy use of exception wrapping, so log the
      // entire exception chain to help with troubleshooting.
      do {
        // @todo Log additional information from ResourceException, to help with
        // debugging, in https://www.drupal.org/project/drupal/issues/2972846.
        $this->logger->error($e->getMessage());
        $e = $e->getPrevious();
      } while ($e);
    }

    return $response;
  }

}
