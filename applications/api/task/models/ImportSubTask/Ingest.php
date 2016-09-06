<?php

namespace ANDS\API\Task\ImportSubTask;

use ANDS\RecordData;
use ANDS\RegistryObject;
use ANDS\Util\XMLUtil;

class Ingest extends ImportSubTask
{
    public function run_task()
    {
        foreach ($this->parent()->getPayloads() as $path=>$xml) {
            $registryObjects = XMLUtil::getElementsByName($xml, 'registryObject');
            foreach ($registryObjects as $registryObject) {
                $this->insertRegistryObject($registryObject);
            }
        }
    }

    public function insertRegistryObject($registryObject)
    {
        $key = trim((string) $registryObject->key);

        // check existing one
        if ($matchingRecord = $this->getMatchingRecord($key)) {
            $this->log("Record key:($key) exists with id:($matchingRecord->registry_object_id). Adding new current version.");

            // deal with previous versions
            RecordData::where('registry_object_id', $matchingRecord->registry_object_id)
                ->update(['current' => '']);

            // add new version in and set it to current
            $newVersion = $this->addNewVersion(
                $matchingRecord->registry_object_id,
                XMLUtil::wrapRegistryObject(
                    $registryObject->saveXML()
                )
            );
            $this->log("Added new Version:$newVersion->id");

            $matchingRecord->setRegistryObjectAttribute('modified', time());

            $this->parent()->addTaskData("importedRecords", $matchingRecord->registry_object_id);

        } else {
            $this->log("Record $key does not exist. Creating new record and data");

            // create new record
            $ro = new RegistryObject;
            $ro->key = $key;
            $ro->data_source_id = $this->parent()->dataSourceID;
            $ro->status = $this->parent()->getTaskData("dataSourceDefaultStatus");
            $ro->save();
            $ro->setRegistryObjectAttribute('created', time());

            // create a new record data
            $newVersion = $this->addNewVersion(
                $ro->registry_object_id,
                XMLUtil::wrapRegistryObject(
                    $registryObject->saveXML()
                )
            );

            $this->log("Record id:$ro->registry_object_id created, key:$key with record data: id:$newVersion->id");

            // TODO: add this record to the imported records
            $this->parent()->addTaskData("importedRecords", $ro->registry_object_id);
        }
    }

    /**
     * TODO: refactor to RecordDataRepository
     * @param $registryObjectID
     * @param $xml
     * @return RecordData
     */
    public function addNewVersion($registryObjectID, $xml)
    {
        $newVersion = new RecordData;
        $newVersion->current = true;
        $newVersion->registry_object_id = $registryObjectID;
        $newVersion->saveData($xml);
        $newVersion->save();
        return $newVersion;
    }

    public function getMatchingRecord($key) {
        $dataSourceDefaultStatus = $this->parent()
            ->getTaskData("dataSourceDefaultStatus");
        $matchingStatusRecords = RegistryObject::where('key', $key)
            ->where('status', $dataSourceDefaultStatus)->first();
        return $matchingStatusRecords;
    }
}