<?php

namespace App\Gci;

use stdClass;
use Carbon\Carbon;
use App\Affiliation;

/**
 * @property-read object $payload
 * @property-read string $hgncId
 * @property-read string $mondoId
 * @property-read string $status
 * @property-read string $moi
 * @property-read stdClass $affiliation
 * @property-read string $classification
 * @property-read string $messageDate
 */
class GciMessage
{
    protected $payload;
    public function __construct($payload)
    {
        if (is_string($payload)) {
            $payload = json_decode($payload);
        }
        $this->payload = $payload;
    }


    public function __get($key)
    {
        $methodName = 'get'.ucfirst($key);
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }
    }

    public function getPayload():stdClass
    {
        return $this->payload;
    }

    public function getUuid():string
    {
        return $this->payload->report_id;
    }

    public function getHgncId():string
    {
        return $this->payload->gene_validity_evidence_level->genetic_condition->gene;
    }

    public function getMondoId():string
    {
        return $this->payload->gene_validity_evidence_level->genetic_condition->condition;
    }

    public function getMoi():string
    {
        return $this->payload->gene_validity_evidence_level->genetic_condition->mode_of_inheritance;
    }

    public function getAffiliation():stdClass
    {
        return (object)$this->payload->performed_by->on_behalf_of;
    }

    public function getStatus():string
    {
        if (is_object($this->payload->status) && isset($this->payload->status->name)) {
            return $this->payload->status->name;
        }

        return $this->payload->status;
    }

    public function getClassification():string
    {
        return $this->payload->gene_validity_evidence_level->evidence_level;
    }

    public function getMessageDate():Carbon
    {
        return Carbon::parse($this->payload->date);
    }

    public function getStatusDate():Carbon
    {
        if (is_object($this->payload->status) && isset($this->payload->status->date)) {
            return Carbon::parse($this->payload->status->date);
        }
        return Carbon::parse($this->payload->date);
    }
}
