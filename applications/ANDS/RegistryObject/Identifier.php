<?php

namespace ANDS\RegistryObject;

use Illuminate\Database\Eloquent\Model;

class Identifier extends Model
{
    protected $table = "registry_object_identifiers";
    protected $primaryKey = "id";
    public $timestamps = false;
    protected $fillable = ['registry_object_id', 'identifier', 'identifier_type'];
}