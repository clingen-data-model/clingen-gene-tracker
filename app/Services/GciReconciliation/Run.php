<?php
namespace App\Services\GciReconciliation;
use Illuminate\Database\Eloquent\Model;
class Run extends Model
{
    protected $table = 'gci_reconciliation_runs';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    public function uploads() { return $this->hasMany(Upload::class, 'run_uuid', 'uuid'); }
}
