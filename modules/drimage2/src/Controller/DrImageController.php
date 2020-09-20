<?php

namespace Drupal\drimage2\Controller;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\file\Entity\File;
use Drupal\image\Controller\ImageStyleDownloadController;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Simple extension over the default image download controller.
 *
 * We inherit from it so we have all functions and logic available. We just
 * override the way the image is generated to suit the needs of the dynamically
 * generated image styles.
 */
class DrImageController extends ImageStyleDownloadController {

  /**
   * Given a raw width and height: check if it adheres to the settings.
   *
   * @param int $width
   *   The raw requested width.
   * @param int $height
   *   The raw requested height.
   *
   * @return bool
   *   Indicates valid width/height against the settings.
   */
  public function checkRequestedDimensions($width, $height) {
    if ($width != intval($width) || $height != intval($height)) {
      return FALSE;
    }

    // Check if the width is between the defined min/max settings.
    $drimage_config = $this->config('drimage2.settings');
    if ($width > $drimage_config->get('downscale') || $width < $drimage_config->get('upscale')) {
      return FALSE;
    }

    // If the width is not at the maximum, check if it is at an exact threshold
    // multiplier, taking into account the minimum value.
    if ($width != $drimage_config->get('downscale')) {
      if (($width - $drimage_config->get('upscale')) % $drimage_config->get('threshold') != 0) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Try and find an image style that matches the requested dimensions.
   *
   * @param array $requested_dimensions
   *   The calculated requested dimensions.
   *
   * @return mixed
   *   A matching image style or NULL if none was found.
   */
  public function findImageStyle(array $requested_dimensions) {
    // Try and get an exact match:
    $name = 'drimage2_' . $requested_dimensions[0] . '_' . $requested_dimensions[2];

    $image_style = ImageStyle::load($name);

    // No usable image style could be found, so we will have to create one.
    if (empty($image_style)) {
      // When the site starts from a cold cache situation and a lot of requests
      // come in, the webserver might fail at this point, so try a few times.
      $counter = 0;
      while (empty($image_style) && $counter < 10) {
        usleep(rand(10000, 50000));
        $image_style = $this->createDrimageStyle($requested_dimensions);
        $counter++;
      }
    }

    return $image_style;
  }

  /**
   * Create an image style from the requested dimensions.
   *
   * @param array $requested_dimensions
   *   The array containing the dimensions.
   *
   * @return mixed
   *   The image style or FALSE in case something went wrong.
   */
  public function createDrimageStyle(array $requested_dimensions) {
    $name = 'drimage2_' . $requested_dimensions[0] . '_' . $requested_dimensions[2];
    $label = 'DrImage2 (' . $requested_dimensions[0] . 'w, ' . $requested_dimensions[2] . ' crop)';

    // When multiple images width the same dimension are requested in 1 page
    // we can sometimes trigger errors here. Image style can already be
    // created by another request that came in a few milliseconds before this
    // request. Catch that error and try and use the image style that was
    // already created.
    try {
      $style = ImageStyle::create(['name' => $name, 'label' => $label]);
      $configuration = [
        'id' => 'image_scale',
        'uuid' => NULL,
        'weight' => 0,
        'data' => [
          'upscale' => TRUE,
          'width' => $requested_dimensions[0],
          'height' => NULL,
        ],
      ];
      $effect = \Drupal::service('plugin.manager.image.effect')->createInstance($configuration['id'], $configuration);
      $style->addImageEffect($effect->getConfiguration());
      // Add manual crop image effect if requested.
      if($requested_dimensions[2]) {
        $configuration = [
          'id' => 'crop_crop',
          'uuid' => NULL,
          'weight' => -20,
          'data' => [
            'crop_type' => $requested_dimensions[2],
          ],
        ];
        $effect = \Drupal::service('plugin.manager.image.effect')->createInstance($configuration['id'], $configuration);
        $style->addImageEffect($effect->getConfiguration());
      }
      $style->save();
      $styles[$name] = $style;
      $image_style = $styles[$name];
    }
    catch (EntityStorageException $e) {
      // Wait a tiny little bit to make sure another request isn't still adding
      // effects to the image style.
      usleep(rand(10000, 50000));
      $image_style = ImageStyle::load($name);
    }
    catch (Exception $e) {
      return NULL;
    }

    return $image_style;
  }

  /**
   * Deliver an image from the requested parameters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param int $width
   *    The requested width in pixels that came from the JS.
   * @param int $height
   *    The requested height in pixels that came from the JS.
   * @param string $crop_type
   *    The requested crop type machine name that came from the JS.
   * @param int $fid
   *    The file id to render.
   * @param string $filename
   *    The filename, only here for SEO purposes.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
   *   The transferred file as response or some error response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user does not have access to the file.
   * @throws \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException
   *   Thrown when the file is still being generated.
   */
  public function image(Request $request, $width, $height, $crop_type, $fid, $filename) {
    // Bail out if the image is not valid.
    $file = File::load($fid);
    $image = $this->imageFactory->get($file->getFileUri());
    if (!$image->isValid()) {
      return new Response($this->t('Error generating image, invalid file.'), 500);
    }

    // Bail out if the arguments are not numbers.
    if (!is_numeric($width) || !is_numeric($height) || !is_numeric($fid)) {
      $error_msg = $this->t('Error generating image, invalid parameters.');
    }

    // The Javascript should have generated a nice size adhering to the
    // threshold and x/y up/down-scaling settings. Check if it actually did.
    // Return the fallback image if it didn't.
    if (!$this->checkRequestedDimensions($width, $height)) {
      $error_msg = $this->t('Error generating image, invalid dimensions.');
    }

    // Try and find a matching image style.
    $requested_dimensions = [0 => $width, 1 => $height, 2 => $crop_type];
    $image_style = $this->findImageStyle($requested_dimensions);
    if (empty($image_style)) {
      $error_msg = $this->t('Could not find matching image style.');
    }

    // Variable translation to make the original imageStyle deliver method work.
    $image_uri = explode('://', $file->getFileUri());
    $scheme = $image_uri[0];
    $request->query->set('file', $image_uri[1]);

    // Use the fallback image style if something went wrong.
    if (!empty($error_msg)) {
      $drimage_config = $this->config('drimage2.settings');
      $fallback_style = $drimage_config->get('fallback_style');
      if (!empty($fallback_style)) {
        $image_style = ImageStyle::load($fallback_style);
      }
    }

    if (!empty($image_style)) {
      // Because drimage does not use itok, we simulate it.
      if (!$this->config('image.settings')->get('allow_insecure_derivatives')) {
        $image_uri = $image_uri[0] . '://' . $image_uri[1];
        $request->query->set(IMAGE_DERIVATIVE_TOKEN, $image_style->getPathToken($image_uri));
      }

      // Uncomment to test the loading effect:
      //usleep(1000000);

      $response = $this->deliver($request, $scheme, $image_style);
      $drimage_config = $this->config('drimage2.settings');
      $proxy_cache_maximum_age = $drimage_config->get('proxy_cache_maximum_age');
      if (!empty($proxy_cache_maximum_age)) {
        $response->setMaxAge($proxy_cache_maximum_age);
      }

      return $response;
    }

    return new Response($error_msg, 500);
  }

}
