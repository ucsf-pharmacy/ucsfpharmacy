<?php

namespace Drupal\Tests\ucsfpharmacy\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;
use Drupal\Tests\ckeditor\Traits\CKEditorAdminSortTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests ucsfpharmacy.module media alterations.
 */
class CKEditorIntegrationTest extends WebDriverTestBase {

  use CKEditorTestTrait;
  use CKEditorAdminSortTrait;
  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'seven';

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The image file to use in tests.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $image;

  /**
   * The media item to embed.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor',
    'crop',
    'file',
    'media_library',
    // 'media_svg',
    'node',
    // 'svg_image_field',
    'text',
    'ucsfpharmacy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'media_embed' => ['status' => TRUE],
        'filter_size' => ['status' => TRUE],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'rows' => [
            [
              [
                'name' => 'Embeds',
                'items' => [
                  'DrupalMediaLibrary',
                ],
              ],
            ],
          ],
        ],
      ],
    ])->save();

    // Note that media_install() grants 'view media' to all users by default.
    $this->adminUser = $this->drupalCreateUser([
      'use text format test_format',
      'access media overview',
      'bypass node access',
    ]);

    // Create a sample media entity to be embedded.
    $this->createMediaType('image', ['id' => 'image', 'label' => 'image']);
    $this->image = File::create([
      'uri' => drupal_get_path('module', 'ucsfpharmacy') . '/tests/files/image1.jpg',
    ]);
    $this->image->save();
    $this->media = Media::create([
      'bundle' => 'image',
      'name' => 'Fear is the mind-killer',
      'field_media_image' => [
        [
          'target_id' => 1,
          'alt' => 'default alt',
          'title' => 'default title',
        ],
      ],
    ]);
    $this->media->save();

    // Create a sample host entity to embed media in.
    $this->drupalCreateContentType(['type' => 'blog']);
    $this->host = $this->createNode([
      'type' => 'blog',
      'title' => 'Test page',
      'body' => [
        'value' => '<drupal-media data-entity-type="media" data-entity-uuid="' . $this->media->uuid() . '"></drupal-media>',
        'format' => 'test_format',
      ],
    ]);
    $this->host->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Waits for the form that allows editing metadata.
   *
   * @see \Drupal\media\Form\EditorMediaDialog
   */
  protected function waitForMetadataDialog() {
    $page = $this->getSession()->getPage();
    $this->getSession()->switchToIFrame();
    // Wait for the dialog to open.
    $result = $page->waitFor(10, function ($page) {
      $metadata_editor = $page->find('css', 'form.editor-media-dialog');
      return !empty($metadata_editor);
    });
    $this->assertTrue($result);
  }

  /**
   * Test data-size attribute exists.
   */
  public function testDataSizeAttribute() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media', 2000));
    $page->pressButton('Edit media');
    $this->waitForMetadataDialog();
    $assert_session->fieldExists('attributes[data-size]');
  }

}
