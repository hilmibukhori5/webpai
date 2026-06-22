<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\ManualSubmissionsImport;
use App\Models\ManualSubmission;
use App\Models\PaiModule;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ManualSubmissionController extends Controller
{
    public function create(): View
    {
        $this->authorize('viewAny', Submission::class);

        $totalEntries = ManualSubmission::count();

        $summary = ManualSubmission::selectRaw('pai_module_id, COUNT(*) as total')
            ->with('paiModule')
            ->groupBy('pai_module_id')
            ->get()
            ->sortBy('paiModule.code');

        return view('admin.manual-submissions.create', compact('totalEntries', 'summary'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Submission::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
            'note' => ['nullable', 'string', 'max:100'],
        ]);

        $import = new ManualSubmissionsImport($request->input('note'));

        try {
            Excel::import($import, $request->file('file'));
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal membaca file. Pastikan formatnya sesuai (xlsx/xls/csv) dan tidak corrupt.');
        }

        $imported = $import->importedCount();
        $skipped = $import->skippedCount();

        $msg = "{$imported} entri berhasil diimport.";
        if ($skipped > 0) {
            $msg .= " {$skipped} baris dilewati (format modul tidak dikenali).";
        }

        return back()->with('status', $msg);
    }

    public function destroy(): RedirectResponse
    {
        $this->authorize('viewAny', Submission::class);

        ManualSubmission::truncate();

        return back()->with('status', 'Semua data pengajuan manual dihapus.');
    }
}
