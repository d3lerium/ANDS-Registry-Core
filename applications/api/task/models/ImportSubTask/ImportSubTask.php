<?php
namespace ANDS\API\Task\ImportSubTask;


use ANDS\API\Task\ImportTask;
use ANDS\API\Task\Task;

class ImportSubTask extends Task
{
    private $parentTask;
    protected $requirePayload = false;
    protected $requireImportedRecords = false;
    protected $requireDeletedRecords = false;
    protected $requireAffectedRecords = false;

    public function run()
    {
        if ($this->requirePayload && $this->parent()->hasPayload() === false) {
            $this->addError("Payload require for this task");
            $this->setStatus("COMPLETED");
            return;
        }

        if ($this->requireImportedRecords) {
            $importedRecords = $this->parent()->getTaskData("importedRecords");
            if ($importedRecords === false || $importedRecords === null) {
                $this->addError("Imported Records require for this task");
                $this->setStatus("COMPLETED");
                return;
            }
        }

        if ($this->requireDeletedRecords) {
            $deletedRecords = $this->parent()->getTaskData("deletedRecords");
            if ($deletedRecords === false || $deletedRecords === null) {
                $this->log("Deleted Records require for this task");
                $this->setStatus("COMPLETED");
                return;
            }
        }

        return parent::run();
    }

    /**
     * @param $task
     * @return $this
     */
    public function setParentTask($task)
    {
        $this->parentTask = $task;
        return $this;
    }

    /**
     * @return ImportTask
     */
    public function getParentTask()
    {
        return $this->parentTask;
    }

    /**
     * Alias for getParentTask
     * for simpler usage
     *
     * @return ImportTask
     */
    public function parent()
    {
        return $this->getParentTask();
    }

    public function log($log)
    {
        $this->message['log'][] = $log;
        $this->parent()->log(get_class($this) . ": " . $log);
        return $this;
    }

    public function addError($log)
    {
        // create error log if not exist
        if (!array_key_exists('error', $this->message)) {
            $this->message['error'] = [];
        }

        // log to local error log as well as parent's error log
        $this->message['error'][] = $log;
        $this->parent()->message['error'][] = get_class($this) . "(ERROR) " . $log;

        // log to message log as well
        $this->log(get_class($this) . "(ERROR) " . $log);
        return $this;
    }

}

//@todo move to ANDS\API\Task\Exception?
class NonFatalException extends \Exception
{

}