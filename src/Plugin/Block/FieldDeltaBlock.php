<?php

namespace Drupal\ucsfpharmacy\Plugin\Block;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Form\FormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_builder\Plugin\Block\FieldBlock;

/**
 * Provides a block that renders a field from an entity.
 * Also allows specifying a field delta.
 *
 * @Block(
 *   id = "field_delta_block",
 *   deriver = "\Drupal\layout_builder\Plugin\Derivative\FieldBlockDeriver",
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
