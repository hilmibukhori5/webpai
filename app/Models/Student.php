<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'no_induk',
        'nama',
        'prodi',
        'payment_status',
        'bukti_pembayaran_path',
        'formulir_terisi_path',
        'decision_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'decision_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function refreshPaymentStatus(): void
    {
        $isPaid = filled($this->bukti_pembayaran_path) && filled($this->formulir_terisi_path);

        $this->update(['payment_status' => $isPaid ? 'paid' : 'unpaid']);
    }
}
