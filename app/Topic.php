<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $fillable = [
        'gene_symbol',
        'expert_panel_id',
        'curator_id',
    ];

    public function expertPanel()
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    public function curator()
    {
        return $this->belongsTo(User::class, 'curator_id');
    }
}
