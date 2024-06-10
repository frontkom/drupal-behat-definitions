<?php

namespace Frontkom\DrupalBehatDefinitions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Some steps to use with Drupal Gutenberg.
 */
class DrupalGutenbergContext extends RawDrupalContext {

  /**
   * Get the text format to use.
   *
   * This is to make it possible to override in a subclass.
   */
  protected function getTextFormat() {
    return 'gutenberg';
  }

  /**
   * Step to write in a gutenberg field.
   *
   * @Then I create gutenberg content from file :file in :entity_type with title :title and field name :field_name
   * @Then I create gutenberg content from file :file in content :title and field name :field_name
   * @Then I create gutenberg content from file :file in content :title
   */
  public function appendContentFromFile($file, $title, $field_name = 'body', $entity_type = 'node') {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $definition = \Drupal::entityTypeManager()->getDefinition($entity_type);
    $entities = $storage->loadByProperties([$definition->getKey('label') => $title]);
    $entity = reset($entities);
    if (!$entity instanceof EntityInterface) {
      throw new \Exception("Entity with title $title not found");
    }
    if (!$entity->hasField($field_name)) {
      throw new \Exception("Entity with title $title does not have field $field_name");
    }
    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
    $field = $entity->get($field_name);
    $format = $this->getTextFormat();
    $content = file_get_contents(DRUPAL_ROOT . '/../tests/files/gutenberg/' . $file);
    $current_content = '';
    if (!$field->isEmpty()) {
      $value = $field->first()->getValue();
      $current_content = $value['value'];
    }
    $field->setValue([
      'value' => sprintf("%s\n%s", $current_content, $content),
      'format' => $format,
    ]);
    $entity->save();
  }

}
