<?php

namespace Frontkom\CommonBehatDefinitions;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Class Debugging.
 *
 * Provide Behat step-definitions for actions helpful when debugging.
 */
class DebuggingContext extends RawDrupalContext {

  /**
   * Defines if logs should be deleted after each scenario.
   *
   * @var string
   */
  protected $deleteLogsPerScenario;

  /**
   * DebuggingContext constructor.
   */
  public function __construct($deleteLogsPerScenario = FALSE) {
    $this->deleteLogsPerScenario = $deleteLogsPerScenario;
  }

  /**
   * Prints from watchdog at failed step.
   *
   * @AfterStep
   */
  public function logDumpAfterStep(AfterStepScope $scope) {
    $failed = (99 === $scope->getTestResult()->getResultCode());
    $database = \Drupal::database();

    if ($failed && $database->schema()->tableExists('watchdog')) {
      $query = $database->select('watchdog', 'w');

      $query
        ->range(0, 30)
        ->fields('w')
        ->orderBy('wid', 'DESC');
      $rsc = $query->execute();
      $table = [];
      while ($result = $rsc->fetchObject()) {
        $table[$result->wid] = (array) $result;
      }
      print_r($table);
    }
  }

  /**
   * Deletes from watchdog the logs that won't be related to the next scenario.
   *
   * @AfterScenario
   */
  public function deleteLogs() {
    if (!$this->deleteLogsPerScenario) {
      return;
    }

    $query = \Drupal::database()
      ->delete('watchdog');

    $query->execute();
  }

}
