<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'reporter_id',
        'target_type',
        'target_id',
        'reason',
        'details',
        'status',
        'admin_note',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function target()
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function resolve(string $note = null): void
    {
        $this->update([
            'status' => 'resolved',
            'admin_note' => $note,
        ]);
    }

    public function reject(string $note = null): void
    {
        $this->update([
            'status' => 'rejected',
            'admin_note' => $note,
        ]);
    }

    public static function reasons(): array
    {
        return [
            'spam' => 'Spam or misleading',
            'inappropriate' => 'Inappropriate content',
            'violence' => 'Violence or harmful behavior',
            'harassment' => 'Harassment or bullying',
            'copyright' => 'Copyright infringement',
            'other' => 'Other',
        ];
    }
}
