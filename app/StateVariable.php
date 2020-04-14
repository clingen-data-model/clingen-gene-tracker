<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Venturecraft\Revisionable\RevisionableTrait;

class StateVariable extends Model
{
    use RevisionableTrait;

    protected $revisionCreationsEnabled = true;

    public $fillable = [
        'name',
        'type',
        'value'
    ];

    public function setValueAttribute($value)
    {
        switch ($this->type) {
            case 'boolean':
                $this->attributes['value'] = (string)((integer)$value);
                break;
            case 'array':
                $this->attributes['value'] = json_encode($value);
                break;
            case 'object':
                $this->attributes['value'] = json_encode($value);
                break;
            default:
                $this->attributes['value'] = (string)$value;
                break;
        }
    }

    public function getValueAttribute()
    {
        switch ($this->type) {
            case 'string':
            case 'integer':
            case 'boolean':
            case 'float':
                settype($this->attributes['value'], $this->type);
                return $this->attributes['value'];
                break;
            case 'array':
                return json_decode($this->attributes['value'], true);
                break;
            case 'object':
                return json_decode($this->attributes['value']);
                break;
            default:
                throw new InvalidArgumentException('Unknown type of state variable: '.$this->attritbues['type']);
                break;
        }
    }
}
