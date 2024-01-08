<?php

namespace Frontkom\DrupalBehatDefinitions;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Defines useful common drupal behat things, steps and so on.
 */
class DrupalFeatureContext extends RawDrupalContext {
  const LAST_WATCHDOG_TIME = 'frontkom-behat-drupal:last-wd';
  use FailOnWatchDogTrait;

}
