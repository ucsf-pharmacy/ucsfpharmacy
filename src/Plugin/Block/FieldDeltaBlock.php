<?php

namespace Drupal\ucsfpharmacy\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Plugin\Block\FieldBlock;

/**
 * Provides a block that stores configuration for a delta value.
 * The delta value can then be used in a downstream function, such as
 * template_preprocess_block(), to only display the field item at the selected
 * delta.
 *
 * @Block(
 *   id = "field_delta_block",
 *   deriver = "\Drupal\ucsfpharmacy\Plugin\Derivative\FieldDeltaBlockDeriver",
 * )
 */
class FieldDeltaBlock extends FieldBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'formatter' => [
        'label' => 'hidden',
        'type' => 'entity_reference_entity_view',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $delta_options = [];
    for($i = 0; $i < 100; $i++) {
      $delta_options[] = $i;
    }

    $form['delta'] = array(
      '#type' => 'select',
      '#title' => $this->t('Delta'),
      '#default_value' => $config['delta'],
      '#options' => $delta_options,
      '#description' => $this->t('Represents the item index in a multiple value field, eg. delta 0 is the first item, 1 is the second, etc.'),
    );

		return parent::blockForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['delta'] = $form_state->getValue('delta');

    return parent::blockSubmit($form, $form_state);
  }

}
