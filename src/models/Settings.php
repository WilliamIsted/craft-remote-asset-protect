<?php
namespace williamisted\remoteassetprotect\models;

use craft\base\Model;

class Settings extends Model
{

    public $settingsSchema;
    
    public $commonEnvironments;
    public $uncommonEnvironments;

    public function rules()
    {
        return [];
    }

}