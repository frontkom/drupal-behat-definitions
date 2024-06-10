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

}
