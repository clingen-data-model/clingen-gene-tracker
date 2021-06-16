<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AppState extends Model
{
    public $fillable = [
        'name',
        'description',
        'value',
        'default',
        'type'
    ];

    public static function findByName($name)
    {
        return static::where('name', $name)->first();
    }

    public function getValueAttribute()
    {
        return $this->castValue($this->attributes['value'], $this->type);
    }

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = $this->stringifyValue($value, $this->type);
    }

    public function set($value)
    {
        $this->value = $value;

        return $this;
    }

    private function castValue($value, $type)
    {
        if (is_null($value)) {
            return $value;
        }
        switch ($type) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'bool':
            case 'boolean':
                return (bool)$value;
            case 'float':
                return (float)$value;
            case 'date':
                return Carbon::parse($value);
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    private function stringifyValue($value, $type)
    {
        switch ($type) {
            case 'json':
                return json_encode($value);
                break;
            case 'date':
                return Carbon::parse($value)->format('Y-m-d H:i:s');
            case 'int':
            case 'integer':
            case 'float':
            case 'bool':
            case 'boolean':
            case 'float':
                return (string)$value;
            default:
                return $value;
        }
    }
}
