<?php

namespace Drupal\drimage2\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'dynamic responsive image' formatter.
 *
 * @FieldFormatter(
 *   id = "drimage2",
 *   label = @Translation("Dynamic Responsive Image 2"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class DrImageFormatter extends ImageFormatter {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an DrImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns the handling options.
   *
   * @return array
   *   The image handling options key|label.
   */
  public function imageHandlingOptions() {
    $crop_types = $this->entityTypeManager->getStorage('crop_type')->loadMultiple();
    $options = [0 => 'None'];
    foreach($crop_types as $crop_type) {
      $options[$crop_type->id()] = $crop_type->label();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'crop_type' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    // Do not use an image style here. Drimage calculates one for us.
    unset($element['image_style']);

    $element['crop_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Crop Type'),
      '#default_value' => $this->getSetting('crop_type'),
      '#options' => $this->imageHandlingOptions(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $options = $this->imageHandlingOptions();
    $handler = $this->getSetting('crop_type');
    $args = [
      '@crop_type' => $options[$handler],
    ];

    $summary[] = $this->t('Crop Type: @crop_type', $args);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $files = $this->getEntitiesToView($items, $langcode);

    $config = \Drupal::configFactory()->get('drimage2.settings');
    foreach ($elements as $delta => $element) {
      $elements[$delta]['#item_attributes'] = new Attribute();
      // @todo: remove img-wrap in version 2.
      $elements[$delta]['#item_attributes']['class'] = ['drimage2', 'img-wrap'];
      $elements[$delta]['#theme'] = 'drimage2_formatter';

      $elements[$delta]['#data'] = [
        'fid' => $elements[$delta]['#item']->entity->id(),
        // Add the original filename for SEO purposes.
        'filename' => pathinfo($elements[$delta]['#item']->entity->getFileUri())['basename'],
        // Add needed data for calculations.
        'threshold' => $config->get('threshold'),
        'upscale' => $config->get('upscale'),
        'downscale' => $config->get('downscale'),
        'multiplier' => $config->get('multiplier'),
        'lazy_offset' => $config->get('lazy_offset'),
        'crop_type' => $this->getSetting('crop_type'),
      ];

      // Get original image data. (non cropped, non processed) This is useful when
      // implementing lightbox-style plugins that show the original image.
      $elements[$delta]['#width'] = $element['#item']->getValue()['width'];
      $elements[$delta]['#height'] = $element['#item']->getValue()['height'];
      $elements[$delta]['#alt'] = $element['#item']->getValue()['alt'];
      $elements[$delta]['#data']['original_width'] = $element['#item']->getValue()['width'];
      $elements[$delta]['#data']['original_height'] = $element['#item']->getValue()['height'];
      $elements[$delta]['#data']['original_source'] = file_url_transform_relative(file_create_url($files[$delta]->getFileUri()));

      // Unset the fallback image.
      unset($elements[$delta]['#image']);
    }
    // dpm($elements);
    return $elements;
  }

}
