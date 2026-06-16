<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    protected $fillable = [
        'student_id',
        'pai_module_id',
        'scheme',
        'price',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'bukti_pembayaran_path',
        'formulir_terisi_path',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function paiModule(): BelongsTo
    {
        return $this->belongsTo(PaiModule::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submissionCourses(): HasMany
    {
        return $this->hasMany(SubmissionCourse::class);
    }

    /**
     * Cek kedua dokumen (bukti bayar + formulir terisi) sudah diupload, lalu
     * set payment_status jadi "paid" kalau iya. Dipanggil setiap kali salah
     * satu file diupload (lihat SubmissionDocumentController) -- otomatis,
     * tanpa langkah verifikasi admin (dikonfirmasi user).
     */
    public function refreshPaymentStatus(): void
    {
        $isPaid = filled($this->bukti_pembayaran_path) && filled($this->formulir_terisi_path);

        $this->update(['payment_status' => $isPaid ? 'paid' : 'unpaid']);
    }
}
