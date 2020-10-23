<?php

namespace Drupal\ucsfpharmacy\Plugin\Derivative;

use Drupal\layout_builder\Plugin\Derivative\FieldBlockDeriver;

/**
 * Provides entity field block definitions for every field.
 *
 * @internal
 *   Plugin derivers are internal.
 */
class FieldDeltaBlockDeriver extends FieldBlockDeriver {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = parent::getDerivativeDefinitions($base_plugin_definition);

    foreach($this->derivatives as $derivative_id => $derivative) {
      $this->derivatives[$derivative_id]['category'] .= ' (delta)';
    }

    return $this->derivatives;
  }

}
