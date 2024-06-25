<?php

/**
 * A queue implementation which stores items in the CiviCRM SQL database
 */
class CRM_Queue_Queue_CasereminderSql extends CRM_Queue_Queue_Sql {

  use CRM_Queue_Queue_SqlTrait;

  /**
   * Create a reference to queue. After constructing the queue, one should
   * usually call createQueue (if it's a new queue) or loadQueue (if it's
   * known to be an existing queue).
   *
   * @param array $queueSpec
   *   Array with keys:
   *   - type: string, required, e.g. "interactive", "immediate", "stomp",
   *     "beanstalk"
   *   - name: string, required, e.g. "upgrade-tasks"
   *   - reset: bool, optional; if a queue is found, then it should be
   *     flushed; default to TRUE
   *   - (additional keys depending on the queue provider).
   */
  public function __construct($queueSpec) {
    parent::__construct($queueSpec);
  }

  /**
   * Get the next item.
   *
   * This is identical to parent::claimItem, with one difference: We don't care
   * if queue items are processed in order; therefore, if a queue item has been
   * placed on hold, we'll just get the next one and keep proessing. (Parent
   * method will not claim any items if the oldest one is on hold.)
   *
   * @param int|null $lease_time
   *   Hold a lease on the claimed item for $X seconds.
   *   If NULL, inherit a queue default (`$queueSpec['lease_time']`) or system default (`DEFAULT_LEASE_TIME`).
   * @return object
   *   With key 'data' that matches the inputted data.
   */
  public function claimItem($lease_time = NULL) {
    $lease_time = $lease_time ?: $this->getSpec('lease_time') ?: static::DEFAULT_LEASE_TIME;

    $result = NULL;
    CRM_Core_DAO::executeQuery('LOCK TABLES civicrm_queue_item WRITE;');
    $sql = '
          SELECT id, queue_name, submit_time, release_time, run_count, data
          FROM civicrm_queue_item
          WHERE queue_name = %1
            AND (release_time IS NULL OR UNIX_TIMESTAMP(release_time) < %2)
          ORDER BY weight, id
          LIMIT 1
      ';
    $params = [
      1 => [$this->getName(), 'String'],
      2 => [CRM_Utils_Time::time(), 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $params, TRUE, 'CRM_Queue_DAO_QueueItem');

    if ($dao->fetch()) {
      $nowEpoch = CRM_Utils_Time::getTimeRaw();
      $dao->run_count++;
      $sql = 'UPDATE civicrm_queue_item SET release_time = from_unixtime(unix_timestamp() + %1), run_count = %3 WHERE id = %2';
      $sqlParams = [
        '1' => [CRM_Utils_Time::delta() + $lease_time, 'Integer'],
        '2' => [$dao->id, 'Integer'],
        '3' => [$dao->run_count, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
      $dao->data = unserialize($dao->data);
      $result = $dao;
    }

    CRM_Core_DAO::executeQuery('UNLOCK TABLES;');
    return $result;
  }

}
