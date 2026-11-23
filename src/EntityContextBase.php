<?php

namespace Frontkom\DrupalBehatDefinitions;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Class EntityContextBase.
 *
 * It is supposed to be extended by classes which implement creating entities. It automatically cleans up
 * all the entities created during a single test.
 */
abstract class EntityContextBase extends RawDrupalContext {

  /**
   * An array of entites created in the context.
   *
   * @var array
   */
  protected $entites = [];

  /**
   * Remove entities by names.
   *
   * @Then I remove entities of type :entity_type_id with names :names
   */
  public function iRemoveEntities($entity_type_id, $names) {
    $entities = \Drupal::entityTypeManager()->getStorage($entity_type_id)
      ->loadByProperties([
        'name' => explode(', ', $names),
      ]);
    foreach ($entities as $entity) {
      $entity->delete();
    }
  }

  /**
   * Clean up created entities.
   *
   * @AfterScenario
   */
  public function cleanUpEntities() {
    foreach ($this->entites as $entity) {
      $entity->delete();
    }
  }

}
