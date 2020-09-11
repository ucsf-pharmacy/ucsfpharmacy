<?php

namespace Drupal\ucsfpharmacy\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to set image container size.
 *
 * @Filter(
 *   id = "filter_size",
 *   title = @Translation("Image container size"),
 *   description = @Translation("Uses a <code>data-size</code> attribute on <code>&lt;img&gt;</code> tags to set image container size."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterSize extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-size') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-size]') as $node) {
        // Read the data-align attribute's value, then delete it.
        $size = $node->getAttribute('data-size');
        $node->removeAttribute('data-size');

        // If one of the allowed alignments, add the corresponding class.
        $classes = $node->getAttribute('class');
        $classes = (strlen($classes) > 0) ? explode(' ', $classes) : [];
        $classes[] = 'size-' . $size;
        $node->setAttribute('class', implode(' ', $classes));
      }
      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }
}
