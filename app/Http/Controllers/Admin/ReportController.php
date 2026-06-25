<?php

namespace App\Http\Controllers\Admin;

use App\Exports\EquivalencyReportExport;
use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    /**
     * Halaman laporan penyetaraan (gabungan semua skema).
     */
    public function index(): View
    {
        $this->authorize('viewAny', Submission::class);

        return view('admin.reports.index', [
            'approvedCount' => Submission::where('status', 'approved')->count(),
        ]);
    }

    /**
     * Download laporan penyetaraan gabungan (xlsx), semua skema, kolom Klausul PKS di akhir.
     */
    public function export(): BinaryFileResponse
    {
        $this->authorize('viewAny', Submission::class);

        $filename = 'laporan-penyetaraan-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new EquivalencyReportExport, $filename);
    }
}
