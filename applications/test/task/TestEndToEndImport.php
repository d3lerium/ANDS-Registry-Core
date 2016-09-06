<?php


namespace ANDS\Test;


use ANDS\API\Task\ImportTask;
use ANDS\RecordData;
use ANDS\RegistryObject\Identifier;
use ANDS\RegistryObject;
use ANDS\RegistryObject\RelatedInfoRelationship;
use ANDS\RegistryObject\Relationship;
use ANDS\RegistryObjectAttribute;


class TestEndToEndImport extends UnitTest
{
    /** @test **/
    public function test_it_should_import_a_record()
    {
        $importTask = new ImportTask();
        $importTask->init([
            'params'=>'ds_id=209&batch_id=AUTestingRecordsImport'
        ])->setCI($this->ci)->initialiseTask();

        $importTask->run_task();

        $taskArray = $importTask->toArray();
        $this->assertEquals(
            "PUBLISHED", $taskArray["data"]["dataSourceDefaultStatus"]
        );

        $importTask->run_task();

        $this->assertFalse(
            $importTask->getTaskByName("ValidatePayload")->hasError()
        );
        // $this->assertTrue($importTask->hasPayload());

        $importTask->run_task();

        $this->assertFalse(
            $importTask->getTaskByName("ProcessPayload")->hasError()
        );
        // $this->assertTrue($importTask->hasPayload());

        $importTask->run_task();

        $this->assertTrue(count($importTask->getTaskData('importedRecords') > 0));

        $record = RegistryObject::where('key', 'minh-test-record-pipeline')->first();
        $this->assertTrue($record);

        $importTask->run_task();

        unset($record);
        $record = RegistryObject::where('key', 'minh-test-record-pipeline')->first();

        $this->assertEquals($record->title, "Minh test record pipeline");
        $this->assertEquals($record->type, "collection");
        $this->assertEquals($record->status, "PUBLISHED");
        $this->assertEquals($record->slug, "minh-test-pipeline");
        $this->assertEquals($record->record_owner, "SYSTEM");
        $this->assertEquals($record->group, "AUTestingRecords");
        $this->assertEquals($record->getRegistryObjectAttributeValue('harvest_id'), "AUTestingRecordsImport");

        // ProcessIdentifiers
        $importTask->run_task();

        $this->assertEquals(
            2,
            Identifier::where(
                'registry_object_id', $record->registry_object_id
            )->count()
        );

        // ProcessRelationships
        $importTask->run_task();

        $this->assertEquals(
            10,
            Relationship::where(
                'registry_object_id', $record->registry_object_id
            )->count()
        );

        $this->assertEquals(
            4,
            RelatedInfoRelationship::where(
                'registry_object_id', $record->registry_object_id
            )->count()
        );

        // ProcessQualityMetadata
        $importTask->run_task();

        // check that level_html is generated and quality_level attribute is there
        $this->assertEquals(0, $record->getRegistryObjectAttributeValue('warning_count'));
        $this->assertEquals(0, $record->getRegistryObjectAttributeValue('error_count'));
        $this->assertEquals(3, $record->getRegistryObjectAttributeValue('quality_level'));

        $this->assertTrue($record->getRegistryobjectMetadata("level_html"));
        $this->assertTrue($record->getRegistryobjectMetadata("quality_html"));

        // indexPortal
        $importTask->run_task();

        // check that metadata solr_doc is generated
        $this->assertTrue($record->getRegistryobjectMetadata("solr_doc"));

    }

    public function setUp()
    {
        require_once(API_APP_PATH . 'vendor/autoload.php');

        $importTask = new ImportTask();
        $importTask->bootEloquentModels();

        $record = RegistryObject::where('key', 'minh-test-record-pipeline')->first();

        if ($record) {
            // delete attributes
            RegistryObjectAttribute::where('registry_object_id', $record->registry_object_id)->delete();

            // delete record_data
            RecordData::where('registry_object_id', $record->registry_object_id)->delete();

            // delete identifiers
            Identifier::where(
                'registry_object_id', $record->registry_object_id
            )->delete();

            // delete record
            $record->delete();
        }

    }
}