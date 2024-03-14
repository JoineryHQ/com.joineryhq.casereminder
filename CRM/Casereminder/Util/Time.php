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
   *
   * @param string $datetime A date/time string, suitable as first argument to php's DateTime().
   */
  private function __construct($datetime) {
    $this->initialize($datetime);
  }

  /**
   * Singleton function used to manage this object.
   *
   * @param string $datetime A date/time string, suitable as first argument to php's DateTime().
   *
   * @return CRM_Casereminder_Util_Time
   */
  public static function &singleton($datetime = "now") : CRM_Casereminder_Util_Time {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Casereminder_Util_Time($datetime);
    }
    return self::$_singleton;
  }

  /**
   * Initialize this object with the given time.
   *
   * @param string $datetime A date/time string, suitable as first argument to php's DateTime().
   *
   * @return void
   */
  private function initialize($datetime = "now") : void {
    $this->now = new DateTime($datetime);
    $this->nowOriginal = clone $this->now;
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
   * Modify $now by some amount. Only available if $this->isAlterAllowed().
   * Modified instance may be reverted to its own original time with self::revert().
   *
   * @param String $modifier  A date/time string, in a format supported by DateTime::modify()
   *   Valid formats are explained in https://www.php.net/manual/en/datetime.formats.php
   *
   * @return void
   */
  public function modify($modifier) : void {
    $this->errorIfAlterNotAllowed(__METHOD__);
    $this->now->modify($modifier);
  }

  /**
   * Completely re-initialize with given time. Only available if $this->isAlterAllowed().
   * This is a complete re-initialization, so that hereafter, self::revert() will
   * revert to the $datetime given here.
   *
   * @param string $datetime
   */
  public function reinitialize($datetime = "now") : void{
    $this->errorIfAlterNotAllowed(__METHOD__);
    $this->initialize($datetime);
  }

  /**
   * Revert to original time. Only available if $this->isAlterAllowed().
   * Original time is whatever was set at the most recent of:
   *  a) singleton instantiation
   *  b) most recent self::reinitialize() call.
   */
  public function revert() {
    $this->errorIfAlterNotAllowed(__METHOD__);
    $this->now = clone($this->nowOriginal);
  }

  /**
   * Is this object allowed to be altered? (Hint: Only if we're in a unit test.)
   * @return boolean
   */
  public function isAlterAllowed() {
    return (getenv('CIVICRM_UF') == 'UnitTests');
  }

  private function errorIfAlterNotAllowed($method) : void {
    if (!$this->isAlterAllowed()) {
      throw new Exception($method . ' is only available when CIVICRM_UF == "UnitTests".');
    }
  }

}
