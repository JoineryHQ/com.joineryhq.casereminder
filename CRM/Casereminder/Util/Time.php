<?php

/**
 * Class to facilitate calculation of real or artificial "now" timestamp.
 *
 */
class CRM_Casereminder_Util_Time {

  /**
   * @var DateTime
   * The current time.
   */
  private $now;

  private $nowOriginal;

  /**
   * @var object
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   */
  private static $_singleton = NULL;

  /**
   * The constructor. Use self::singleton() to create an instance.
   */
  private function __construct($dateTime = "now") {
    $this->now = new DateTime($dateTime);
    $this->nowOriginal = clone $this->now;
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

  public function getTimestamp() : int {
    return $this->now->getTimestamp();
  }

  public function getFormatted($format) : string {
    return $this->now->format($format);
  }

  /**
   * Modify $now by some amount. Only if $this->isAlterAllowed().
   *
   * @param String $modifier  A date/time string, in a format supported by DateTime::modify()
   *   Valid formats are explained in https://www.php.net/manual/en/datetime.formats.php
   *
   * @return void
   */
  public function modify($modifier) : void {
    if (!$this->isAlterAllowed()) {
      $this->throwAlterNotAllowed(__METHOD__);
    }
    $this->now->modify($modifier);
  }

  public function reset() {
    if (!$this->isAlterAllowed()) {
      $this->throwAlterNotAllowed(__METHOD__);
    }
    $this->now = clone($this->nowOriginal);
  }

  /**
   * Are we allowed to modify $now? (Only when CIVICRM_UF=UnitTests).
   *
   * @return bool
   */
  private function isAlterAllowed() {
    return (getenv('CIVICRM_UF') == 'UnitTests');
  }

  private function throwAlterNotAllowed($method) {
    throw new Exception($method . ' is only available when CIVICRM_UF == "UnitTests".');
  }

}
