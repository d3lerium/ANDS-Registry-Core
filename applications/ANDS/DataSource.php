<?php


namespace ANDS;


use Illuminate\Database\Eloquent\Model;

class DataSource extends Model
{
    protected $table = "data_sources";
    protected $primaryKey = "data_source_id";

    public function dataSourceAttributes()
    {
        return $this->hasMany(DataSourceAttribute::class, "data_source_id", "data_source_id");
    }

    public function attr($key)
    {
        return DataSourceAttribute::where('data_source_id', $this->data_source_id)
            ->where('attribute', $key)->first()->value;

        // Alternative way to get attributes, maybe slower
        /*return $this->dataSourceAttributes->filter(function($value) use ($key){
            return $value->attribute == $key;
        })->first()->value;*/
    }


    public function setDatSourceAttribute($key, $value)
    {

        if ($existingAttribute = DataSourceAttribute::where('attribute', $key)
            ->where('data_source_id', $this->data_source_id)->first()
        ) {
            $existingAttribute->value = $value;
            return $existingAttribute->save();
        } else {
            return RegistryObjectAttribute::create([
                'data_source_id' => $this->data_source_id,
                'attribute' => $key,
                'value' => $value
            ]);
        }
    }

    public function getDataSourceAttribute($key)
    {
        return DataSourceAttribute::where('data_source_id', $this->data_source_id)
            ->where('attribute', $key)->first();
    }
    
}