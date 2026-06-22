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
     * Halaman pilih skema laporan (Adendum PKS Lama/Baru) sebelum download.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Submission::class);

        return view('admin.reports.index', [
            'approvedLamaCount' => Submission::where('status', 'approved')->where('scheme', 'lama')->count(),
            'approvedBaruCount' => Submission::where('status', 'approved')->where('scheme', 'baru')->count(),
        ]);
    }

    /**
     * Download laporan penyetaraan (xlsx) untuk satu skema.
     */
    public function export(string $scheme): BinaryFileResponse
    {
        $this->authorize('viewAny', Submission::class);

        abort_unless(in_array($scheme, ['lama', 'baru'], true), 404);

        $filename = 'laporan-penyetaraan-pks-'.$scheme.'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new EquivalencyReportExport($scheme), $filename);
    }
}
