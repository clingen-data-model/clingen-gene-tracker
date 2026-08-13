<?php

namespace App\Gci;

use stdClass;
use Carbon\Carbon;
use App\Affiliation;

/**
 * @property-read object $payload
 * @property-read string $hgncId
 * @property-read string $mondoId
 * @property-read ?string $status
 * @property-read ?string $moi
 * @property-read ?stdClass $affiliation
 * @property-read string $classification
 * @property-read Carbon $messageDate
 * @property-read ?string $transferToId 
 * @property-read ?string $transferToGcepId
 * @property-read ?string $transferFromId
 * @property-read ?string $transferFromGcepId
 * @property-read ?Carbon $transferDate
 
 
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

    public function getMoi(): ?string
    {
        return $this->payload->gene_validity_evidence_level->genetic_condition->mode_of_inheritance ?? null;
    }

    public function getAffiliation(): ?stdClass
    {
        if (!isset($this->payload->performed_by->on_behalf_of)) { return null; }
        return (object)$this->payload->performed_by->on_behalf_of;
    }

    // Additional methods to access the transfer Affiliation from and to from the payload. GT-73 Issue with Transfer Curation
    public function getTransferToId(): ?string
    {
        if (!$this->isGdmTransfer()) { return null; }
        return $this->getContent()->transfer_to->id ?? null;
    }

    public function getTransferToGcepId(): ?string
    {
        if (!$this->isGdmTransfer()) { return null; }
        return $this->getContent()->transfer_to->gcep_id ?? null;
    }

    public function getTransferFromId(): ?string
    {
        if (!$this->isGdmTransfer()) { return null; }
        return $this->getContent()->transfer_from->id ?? null;
    }
    public function getTransferFromGcepId(): ?string
    {
        if (!$this->isGdmTransfer()) { return null; }
        return $this->getContent()->transfer_from->gcep_id ?? null;
    }

    public function getTransferDate(): ?Carbon
    {
        if (!$this->isGdmTransfer()) { return null; }
        if (isset($this->getContent()->date)) { return Carbon::parse($this->getContent()->date); }
        return $this->messageDate;
    }
    // end of GT-73 Issue with Transfer Curation

    public function hasStatus(): bool
    {
        return (bool)$this->status;
    }
    
    public function getStatus():?string
    {
        if (!isset($this->payload->status)) {
            return null;
        }

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

    public function getStatusDate(): ?Carbon
    {
        if (!$this->status) {
            return null;
        }
        if (is_object($this->payload->status) && isset($this->payload->status->date)) {
            return Carbon::parse($this->payload->status->date);
        }
        return Carbon::parse($this->payload->date);
    }

    public function getCreator():stdClass
    {
        return collect($this->payload->contributors)
            ->filter(function ($c) {
                return in_array('creator', $c->roles);
            })->first();
    }

    private function hasContent(): bool
    {
        return isset($this->payload->content);
    }

    private function hasContentEventType(): bool
    {
        return ($this->hasContent() && isset($this->payload->content->event_type));
    }

    public function getContent():stdClass
    {
        if (isset($this->payload->content)) {
            return $this->payload->content;
        }

        return new stdClass();
    }

    public function getOriginalDisease(): ?string
    {
        if (!$this->hasContent()) {
            return null;
        }

        if (!isset($this->getContent()->original_disease)) {
            return null;
        }

        return $this->getContent()->original_disease;
    }
    
    public function hasContentNotes(): bool
    {
        return $this->hasContent() && isset($this->getContent()->notes);
    }

    public function getContentNotes(): ?string
    {
        if (!$this->hasContentNotes()) {
            return null;
        }

        return $this->getContent()->notes;

    }
    
    

    // METHODS TO CHECK MESSAGE TYPE
    
    public function isCreate(): bool
    {
        return $this->getStatus() == 'created';
    }
    

    public function isGdmTransfer(): bool
    {
        if (!$this->hasContentEventType()) {
            return false;
        }

        if ($this->payload->content->event_type == 'transfer') {
            return true;
        }

        return false;
    }

    public function isDiseaseChange(): bool
    {
        if (!$this->hasContent()) {
            return false;
        }

        if ($this->getContent()->event_type == 'disease change') {
            return true;
        }

        return false;
    }

}
