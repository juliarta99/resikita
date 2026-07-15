<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportAssignment;
use App\Models\ReportProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PetugasController extends Controller
{
    /** Ambil penugasan milik petugas untuk laporan tsb, atau abort 403. */
    private function assignmentAtau403(Report $report, int $petugasId): ReportAssignment
    {
        $a = ReportAssignment::where('report_id', $report->id)->where('petugas_id', $petugasId)->first();
        abort_unless($a, 403, 'Anda tidak ditugaskan pada laporan ini.');

        return $a;
    }

    public function penugasan(Request $request)
    {
        $q = ReportAssignment::where('petugas_id', $request->user()->id)
            ->with(['report.kategori'])
            ->latest('assigned_at');

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        $data = $q->paginate(12)->through(function ($a) {
            $r = $a->report;
            return [
                'assignment_id'   => $a->id,
                'assignment_status' => $a->status,
                'assigned_at'     => $a->assigned_at?->toIso8601String(),
                'report' => $r ? [
                    'id' => $r->id, 'tiket_no' => $r->tiket_no, 'judul' => $r->judul,
                    'kategori' => $r->kategori?->nama, 'status' => $r->status,
                    'alamat' => $r->alamat, 'lat' => $r->lat, 'lng' => $r->lng,
                    'foto' => $r->foto ? asset('storage/' . $r->foto) : null,
                    'tanggal' => $r->created_at?->toIso8601String(),
                ] : null,
            ];
        });

        return response()->json($data);
    }

    public function detail(Request $request, Report $report)
    {
        $assignment = $this->assignmentAtau403($report, $request->user()->id);
        $report->load('kategori', 'banjarDinas', 'images', 'progress.petugas');

        return response()->json(['data' => [
            'id' => $report->id, 'tiket_no' => $report->tiket_no, 'judul' => $report->judul,
            'deskripsi' => $report->deskripsi, 'kategori' => $report->kategori?->nama,
            'status' => $report->status, 'alamat' => $report->alamat,
            'lat' => $report->lat, 'lng' => $report->lng,
            'wilayah' => $report->banjarDinas?->nama,
            'assignment_status' => $assignment->status,
            'foto' => $report->foto ? asset('storage/' . $report->foto) : null,
            'bukti' => $report->images->map(fn ($im) => asset('storage/' . $im->path))->values(),
            'progress' => $report->progress->sortBy('created_at')->map(fn ($p) => [
                'status' => $p->status_progress, 'catatan' => $p->catatan,
                'foto' => $p->foto_bukti ? asset('storage/' . $p->foto_bukti) : null,
                'oleh' => $p->petugas?->name,
                'tanggal' => $p->created_at?->toIso8601String(),
            ])->values(),
        ]]);
    }

    public function progress(Request $request, Report $report)
    {
        $data = $request->validate([
            'catatan'    => 'required|string|max:1000',
            'foto_bukti' => 'nullable|image|max:4096',
            'lat'        => 'nullable|numeric|between:-90,90',
            'lng'        => 'nullable|numeric|between:-180,180',
        ]);

        return $this->catatProgress($request, $report, 'dikerjakan', $data);
    }

    public function selesai(Request $request, Report $report)
    {
        $data = $request->validate([
            'catatan'    => 'required|string|max:1000',
            'foto_bukti' => 'nullable|image|max:4096',
            'lat'        => 'nullable|numeric|between:-90,90',
            'lng'        => 'nullable|numeric|between:-180,180',
        ]);

        return $this->catatProgress($request, $report, 'selesai', $data);
    }

    private function catatProgress(Request $request, Report $report, string $statusProgress, array $data)
    {
        $assignment = $this->assignmentAtau403($report, $request->user()->id);

        if (in_array($report->status, ['selesai', 'ditolak'])) {
            return response()->json(['message' => 'Laporan sudah ditutup.'], 422);
        }

        $foto = $request->hasFile('foto_bukti')
            ? $request->file('foto_bukti')->store('reports', 'public')
            : null;

        DB::transaction(function () use ($request, $report, $assignment, $statusProgress, $data, $foto) {
            ReportProgress::create([
                'report_id'       => $report->id,
                'petugas_id'      => $request->user()->id,
                'catatan'         => $data['catatan'],
                'foto_bukti'      => $foto,
                'status_progress' => $statusProgress,
                'lat'             => $data['lat'] ?? null,
                'lng'             => $data['lng'] ?? null,
            ]);

            if ($statusProgress === 'selesai') {
                $assignment->update(['status' => 'selesai']);
                // Laporan selesai hanya jika semua petugas sudah menyelesaikan
                $masihJalan = ReportAssignment::where('report_id', $report->id)
                    ->where('status', '!=', 'selesai')->exists();
                $report->update(['status' => $masihJalan ? 'proses' : 'selesai']);
            } else {
                $assignment->update(['status' => 'dikerjakan']);
                if ($report->status === 'ditugaskan') {
                    $report->update(['status' => 'proses']);
                }
            }
        });

        return response()->json([
            'message' => $statusProgress === 'selesai' ? 'Penugasan ditandai selesai.' : 'Progress dicatat.',
            'data'    => ['report_status' => $report->fresh()->status, 'assignment_status' => $assignment->fresh()->status],
        ], 201);
    }
}