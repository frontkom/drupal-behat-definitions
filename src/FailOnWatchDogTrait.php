<?php

namespace Frontkom\DrupalBehatDefinitions;

trait FailOnWatchDogTrait {

  /**
   * Fail the step if it generates a warning or notice.
   *
   * @AfterStep
   */
  public function failStepIfPhpInWatchdog() {
    if (!\Drupal::database()->schema()->tableExists('watchdog')) {
      echo "No watchdog table found so there may or may not be notices/warnings that might show up in CI\n";
      return;
    }
    $time_to_check = $_SERVER['REQUEST_TIME'];
    $state_stored_time = \Drupal::state()->get(self::LAST_WATCHDOG_TIME);
    if ($state_stored_time && $state_stored_time > $time_to_check) {
      $time_to_check = $state_stored_time;
    }
    $log_msgs = \Drupal::database()->select('watchdog', 'w')
      ->fields('w')
      ->condition('w.type', 'php')
      // We can use REQUEST_TIME here because this file will be executed by
      // behat when we start the tests. So any watchdog message within the php
      // facility after we started the script, is a notice/warning or similar
      // that running the tests triggered. This is not something we want, is
      // it?
      ->condition('w.timestamp', $time_to_check, '>')
      ->execute();
    foreach ($log_msgs as $msg) {
      // This specific PHP notice/warning is something we can ignore. It ends up
      // being that, but it's really a message saying we are not allowed to
      // access the resource. I mean, if we are testing something with access,
      // surely we have other assertions, right?
      if (strpos($msg->message, 'No authentication credentials provided. in Drupal\basic_auth\Authentication\Provider\BasicAuth->challengeException') !== FALSE) {
        continue;
      }
      // We need to use the state system, since setting it as a property on the
      // class will only live the rest of the step, and not until the next
      // scenario.
      \Drupal::state()->set(self::LAST_WATCHDOG_TIME, time());
      throw new \Exception('Found a PHP warning/notice or similar. The message was: ' . $msg->message);
    }
  }

}
