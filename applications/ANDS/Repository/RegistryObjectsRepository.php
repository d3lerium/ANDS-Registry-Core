<?php

namespace ANDS\Repository;

use ANDS\API\Task\ImportTask;
use ANDS\RegistryObject;
use ANDS\RegistryObjectAttribute;
use ANDS\RegistryObject\Metadata;
use ANDS\RegistryObject\Identifier;
use ANDS\RegistryObject\Relationship;
use ANDS\RecordData;

class RegistryObjectsRepository
{
    /**
     * Delete a single record by ID
     * uses ProcessDelete task to complete the job
     * Does not give more information than true or false
     *
     * @param $id
     * @return bool
     */
    public static function deleteRecord($id)
    {
        $importTask = new ImportTask();
        $importTask->init([])->bootEloquentModels();

        $importTask
            ->setTaskData('deletedRecords', [$id])
            ->setTaskData('subtasks', [['name'=>'ProcessDelete', 'status'=>'PENDING']])
            ->initialiseTask();
        $deleteTask = $importTask->getTaskByName('ProcessDelete');
        $deleteTask->run();

        if ($deleteTask->hasError()) {
            return false;
        }

        return true;
    }

    /**
     * Completely delete
     *
     * @param $id
     */
    public static function completelyEraseRecordByID($id)
    {
        $record = RegistryObject::find($id);
        if ($record) {
            // delete attributes
            RegistryObjectAttribute::where('registry_object_id', $record->registry_object_id)->delete();

            // delete record_data
            RecordData::where('registry_object_id', $record->registry_object_id)->delete();

            // delete identifiers
            Identifier::where('registry_object_id', $record->registry_object_id)->delete();

            // delete metadata
            Metadata::where('registry_object_id', $record->registry_object_id)->delete();

            //delete relationship
            Relationship::where('registry_object_id', $record->registry_object_id)->delete();

            // delete record
            $record->delete();

            // TODO: delete Portal and Relation index?
        }
    }


    /**
     * Completely erase the existence of a record by key
     * use with caution, deletes all status of a key
     *
     * @param $key
     */
    public static function completelyEraseRecord($key)
    {
        $records = RegistryObject::where('key', $key)->get();
        foreach ($records as $record) {
            self::completelyEraseRecordByID($record->registry_object_id);
        }
    }

    /**
     * Get the published version of a record by key
     *
     * @param $key
     * @return mixed
     */
    public static function getPublishedByKey($key)
    {
        return self::getByKeyAndStatus($key, 'PUBLISHED');
    }

    /**
     * Useful function to get record by key and status
     *
     * @param $key
     * @param string $status
     * @return mixed
     */
    public static function getByKeyAndStatus($key, $status = "PUBLISHED")
    {
        $importTask = new ImportTask();
        $importTask->init([])->bootEloquentModels();

        return RegistryObject::where('key', $key)->where('status', $status)->first();
    }


    /**
     * Useful function to get record-count by data_source_id and status
     *
     * @param $dataSourceId
     * @param string $status
     * @return integer
     */
    public static function getCountByDataSourceIDAndStatus($dataSourceId, $status)
    {
        $importTask = new ImportTask();
        $importTask->init([])->bootEloquentModels();

        return  RegistryObject::where('data_source_id', $dataSourceId)->where('status', $status)->count();
    }


    public static function getRecordsByHarvestID($harvestId, $dataSourceId, $status = "PUBLISHED")
    {
        $importTask = new ImportTask();
        $importTask->init([])->bootEloquentModels();

        $registryObjects = RegistryObject::where('data_source_id', $dataSourceId)->where('status', $status)->get();

        $registryObjects = $registryObjects->filter(function($obj) use ($harvestId){
            return $obj->hasHarvestID($harvestId);
        });

        return $registryObjects;
    }

    public static function getRecordsByDifferentHarvestID($harvestId, $dataSourceId, $status = "PUBLISHED")
    {
        $importTask = new ImportTask();
        $importTask->init([])->bootEloquentModels();

        $registryObjects = RegistryObject::where('data_source_id', $dataSourceId)->where('status', $status)->get();

        $registryObjects = $registryObjects->filter(function($obj) use ($harvestId){
            return $obj->hasDifferentHarvestID($harvestId);
        });

        return $registryObjects;
    }


    public static function getDraftStatusGroup()
    {
        return [
            "MORE_WORK_REQUIRED",
            "DRAFT",
            "SUBMITTED_FOR_ASSESSMENT",
            "ASSESSMENT_IN_PROGRESS",
            "APPROVED"
        ];
    }

    public static function isDraftStatus($status)
    {
        return in_array($status, self::getDraftStatusGroup());
    }

    public static function getPublishedStatusGroup()
    {
        return ["PUBLISHED"];
    }

    public static function isPublishedStatus($status)
    {
        return in_array($status, self::getPublishedStatusGroup());
    }

    public static function getMatchingRecord($key, $status)
    {
        if(in_array($status, self::getDraftStatusGroup())){
            $inStatus = self::getDraftStatusGroup();
        }else{
            $inStatus = self::getPublishedStatusGroup();
        }
        $matchingStatusRecords = RegistryObject::where('key', $key)
            ->whereIn('status', $inStatus)->first();
        return $matchingStatusRecords;
    }
    
    public static function getDeletedRecord($key)
    {
        $deletedRecord = RegistryObject::where('key', $key)
            ->where('status', 'DELETED')
            ->first();
        return $deletedRecord;
    }
    
}