<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceModification extends Model
{
    use HasFactory;

    protected $table = 'attendance_mod_requests';

    protected $fillable = [
        'attendance_id',
        'requested_clock_in',
        'requested_clock_out',
        'reason',
        'status',
        'comment'
    ];

    protected $casts = [
        'requested_clock_in' => 'datetime',
        'requested_clock_out' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function break_modification_requests()
    {
        return $this->hasMany(BreakModificationRequest::class);
    }
}
