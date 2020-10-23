<?php

namespace Drupal\ucsfpharmacy\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Plugin\Block\FieldBlock;

/**
 * Provides a block that renders a field from an entity.
 * Also allows specifying a field delta.
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
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['delta'] = array(
      '#type' => 'select',
      '#title' => $this->t('Delta'),
      '#default_value' => $config['delta'],
      '#options' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
      '#description' => $this->t(''),
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
