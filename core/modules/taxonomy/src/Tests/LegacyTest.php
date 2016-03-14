<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\LegacyTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use \Drupal\taxonomy\Entity\Vocabulary;

/**
 * Posts an article with a taxonomy term and a date prior to 1970.
 *
 * @group taxonomy
 */
class LegacyTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'datetime');

  protected function setUp() {
    parent::setUp();

    // Create a tags vocabulary for the 'article' content type.
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
    ]);
    $vocabulary->save();
    $field_name = 'field_' . $vocabulary->id();

    $handler_settings = array(
      'target_bundles' => array(
        $vocabulary->id() => $vocabulary->id(),
      ),
      'auto_create' => TRUE,
    );
    $this->createEntityReferenceField('node', 'article', $field_name, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    entity_get_form_display('node', 'article', 'default')
      ->setComponent($field_name, array(
        'type' => 'entity_reference_autocomplete_tags',
      ))
      ->save();

    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy', 'administer nodes', 'bypass node access']));
  }

  /**
   * Test taxonomy functionality with nodes prior to 1970.
   */
  function testTaxonomyLegacyNode() {
    // Posts an article with a taxonomy term and a date prior to 1970.
    $date = new DrupalDateTime('1969-01-01 00:00:00');
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['created[0][value][date]'] = $date->format('Y-m-d');
    $edit['created[0][value][time]'] = $date->format('H:i:s');
    $edit['body[0][value]'] = $this->randomMachineName();
    $edit['field_tags[target_id]'] = $this->randomMachineName();
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));
    // Checks that the node has been saved.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertEqual($node->getCreatedTime(), $date->getTimestamp(), 'Legacy node was saved with the right date.');
  }

}
