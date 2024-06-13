<?php

namespace Frontkom\DrupalBehatDefinitions;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Defines useful common drupal behat things, steps and so on.
 */
class DrupalFeatureContext extends RawDrupalContext {
  const LAST_WATCHDOG_TIME = 'frontkom-behat-drupal:last-wd';
  use FailOnWatchDogTrait;

  /**
   * Step to run a post update hook.
   *
   * @Then I run the post_update hook :hook from module :module
   */
  public function iRunThePostUpdateHookFromModule($hook, $module) {
    /** @var \Drupal\Core\Extension\ModuleHandler $module_handler */
    $module_handler = \Drupal::moduleHandler();
    $module_handler->loadInclude($module, 'php', "$module.post_update");
    // It's possible to include an argument for "sandbox".
    $sandbox = [];
    $hook($sandbox);
  }

  /**
   * Helper to visit content.
   *
   * @Then /^I visit the "([^"]*)" content with title "([^"]*)"$/
   * @Then /^I visit the "([^"]*)" content with title "([^"]*)" in language "([^"]*)"$/
   */
  public function visitContentTypeByTitle($type, $title, $language = NULL) {
    $nid = $this->getContentNid($title, $type);
    $address = 'node/' . $nid;
    if ($language) {
      $address = "$language/node/$nid";
    }
    $this->getSession()->visit($this->locatePath($address));
  }

  /**
   * Helper to get content nid.
   */
  public function getContentNid($title, $type) {
    $nids = \Drupal::entityTypeManager()->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $type)
      ->condition('title', $title)
      ->execute();
    if (empty($nids)) {
      throw new \Exception('No nodes found for ' . $title);
    }
    return reset($nids);
  }

  /**
   * Step definition for attaching files to entities.
   *
   * @Then /^I attach file "([^"]*)" to "([^"]*)" "([^"]*)" "([^"]*)" in field "([^"]*)"$/
   */
  public function iAttachFileToInField($file_name, $entity_type, $content_type, $title, $field) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $definition = \Drupal::entityTypeManager()->getDefinition($entity_type);
    $entities = $storage->loadByProperties([
      $definition->getKey('label') => $title,
      $definition->getKey('bundle') => $content_type,
    ]);
    if (count($entities) === 0) {
      throw new \Exception("No $entity_type found with title $title");
    }
    if (count($entities) > 1) {
      throw new \Exception("Multiple $entity_type found with title $title");
    }
    $entity = reset($entities);
    $file = $this->createFile($file_name);
    $entity->set($field, $file);
    $entity->save();
  }

  /**
   * Helper to create a file from the assets dir.
   */
  public function createFile($path) {
    /** @var \Drupal\file\FileRepositoryInterface $file_repo */
    $file_repo = \Drupal::service('file.repository');
    if ($this->getMinkParameter('files_path')) {
      $fullPath = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
      if (is_file($fullPath)) {
        $path = $fullPath;
      }
    }
    $contents = file_get_contents($path);
    return $file_repo->writeData($contents, sprintf('public://%s', basename($path)));
  }

}
