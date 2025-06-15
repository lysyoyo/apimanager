<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSummary extends Model
{
    use HasFactory;

    public $table = 'event_summary_view';
    public $timestamps = false;

    protected $primaryKey = 'event_id';
}
