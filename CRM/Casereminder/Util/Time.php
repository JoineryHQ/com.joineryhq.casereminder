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
  private function __construct($dateTime = "now") {
    $this->now = new DateTime($dateTime);
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

  public function getDateTime() : DateTime {
    return $this->now;
  }

  public function getMysqlDatetime() : string {
    return $this->now->format('YmdHis');
  }

  public function getDayOfWeek() : string {
    return $this->now->format('l');
  }

  public function getTodayRange() : array {
    $nowTime = $this->now->getTimestamp();
    $rangeMinTime = strtotime('today midnight', $nowTime);
    $rangeMaxTime = $rangeMinTime + (24 * 60 * 60) - 1;
    return [
      date('YmdHis', $rangeMinTime),
      date('YmdHis', $rangeMaxTime),
    ];
      
  }

  public function getFormatted($format) : string {
    return $this->now->format($format);
  }
}
