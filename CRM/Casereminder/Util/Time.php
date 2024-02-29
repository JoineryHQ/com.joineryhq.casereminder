<?php

/**
 * Class to facilitate calculation of real or artificial "now" timestamp.
 *
 */
class CRM_Casereminder_Util_Time {

  /**
   * The current time.
   * @var type
   */
  private $now;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   */
  private static $_singleton = NULL;

  /**
   * The constructor. Use self::singleton() to create an instance.
   */
  private function __construct($timestamp = NULL) {
    if ($timestamp) {
      $timestamp = '@'. $timestamp;
    }
    $this->now = new DateTime($timestamp);
  }

  /**
   * Singleton function used to manage this object.
   *
   * @return Object
   */
  public static function &singleton($timestamp = NULL) : CRM_Casereminder_Util_Time {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Casereminder_Util_Time($timestamp);
    }
    return self::$_singleton;
  }

  public function getNow() : DateTime {
    return $this->now;
  }
}
