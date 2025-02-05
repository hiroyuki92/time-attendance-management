<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTimeModification extends Model
{
    use HasFactory;

    protected $table = 'break_mod_requests';

    protected $fillable = [
        'attendance_mod_request_id',
        'break_times_id',
        'requested_break_start',
        'requested_break_end',
    ];

    protected $casts = [
        'requested_break_start' => 'datetime',
        'requested_break_end' => 'datetime',
    ];

    public function attendance_mod_request()
    {
        return $this->belongsTo(AttendanceModification::class, 'attendance_mod_request_id');
    }

    public function break_time()
    {
        return $this->belongsTo(BreakTime::class, 'break_times_id');
    }

}
