<?php

/**
 * Class to facilitate calculation of real or artificial "now" timestamp.
 *
 */
class CRM_Casereminder_Util_MailerSettings {

  /**
   * @var array
   * The collection of mailer settings.
   */
  private $settings;

  /**
   * @var array
   * Storage of initial mailer settings, so we can revert (for testing only).
   */
  private $settingsOriginal;

  /**
   * @var object
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   */
  private static $_singleton = NULL;

  /**
   * The constructor. Use self::singleton() to create an instance.
   *
   */
  private function __construct() {
    $this->initialize();
  }

  /**
   * Singleton function used to manage this object.
   *
   * @return CRM_Casereminder_Util_Time
   */
  public static function &singleton() : CRM_Casereminder_Util_MailerSettings {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Casereminder_Util_MailerSettings();
    }
    return self::$_singleton;
  }

  /**
   * Initialize this object with the relevant settings from CiviCRM.
   *
   * @return void
   */
  private function initialize() : void {
    $this->settings = [
      'mailerBatchLimit' => \Civi::settings()->get('mailerBatchLimit'),
      'mailThrottleTime' => \Civi::settings()->get('mailThrottleTime'),
    ];
    $this->settingsOriginal = $this->settings;
  }

  public function get(string $name) : string {
    return $this->settings[$name];
  }

  public function getAll() : array {
    return $this->settings;
  }

  /**
   * Alter a setting to the given value. Only available if $this->isAlterAllowed().
   * @param string $name
   * @param string $value
   * @return void
   */
  public function alter(string $name, string $value) : void {
    $this->errorIfAlterNotAllowed(__METHOD__);
    if (array_key_exists($name, $this->settings)) {
      $this->settings[$name] = $value;
    }
  }

  /**
   * Completely re-initialize with current core settings. Only available if $this->isAlterAllowed().
   * This is a complete re-initialization, so that hereafter, self::revert() will
   * revert to the settings as of the last invocation of this method.
   */
  public function reinitialize() : void {
    $this->errorIfAlterNotAllowed(__METHOD__);
    $this->initialize();
  }

  /**
   * Revert to original settings. Only available if $this->isAlterAllowed().
   * Original settings are whatever was set at the most recent of:
   *  a) singleton instantiation
   *  b) most recent self::reinitialize() call.
   */
  public function revert() {
    $this->errorIfAlterNotAllowed(__METHOD__);
    $this->settings = $this->settingsOriginal;
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
