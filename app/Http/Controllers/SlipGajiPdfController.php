<?php

namespace App\Http\Controllers;

use App\Models\Penggajian;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SlipGajiPdfController extends Controller
{
    public function download($id)
    {
        $penggajian = Penggajian::with('user')->findOrFail($id);

        $pdf = Pdf::loadView('pdf.slip-gaji', compact('penggajian'));

        $filename = 'Slip_Gaji_' . $penggajian->user->name . '_' . $penggajian->periode . '.pdf';

        return $pdf->download($filename);
    }

    public function downloadBulk(Request $request)
    {
        $bulan = $request->bulan; // format: YYYY-MM

        $penggajians = Penggajian::with('user')
            ->where('periode', $bulan)
            ->where('status', 'paid')
            ->get();

        if ($penggajians->isEmpty()) {
            return back()->with('error', 'Tidak ada data penggajian untuk periode tersebut');
        }

        $pdf = Pdf::loadView('pdf.slip-gaji-bulk', compact('penggajians', 'bulan'));

        $filename = 'Slip_Gaji_Periode_' . $bulan . '.pdf';

        return $pdf->download($filename);
    }
}
