<?php
namespace App\Services\GciReconciliation;
use Illuminate\Database\Eloquent\Model;
class Upload extends Model
{
    protected $table = 'gci_reconciliation_uploads';
    protected $guarded = [];
    protected $hidden = ['path'];
    protected $casts = ['metadata' => 'array'];
}
