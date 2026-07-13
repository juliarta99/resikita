<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function kategori()
    {
        $data = ReportCategory::orderBy('nama')->get(['id', 'nama', 'deskripsi']);

        return response()->json(['data' => $data]);
    }

    private function tiketUnik(): string
    {
        do {
            $tiket = 'LAP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
        } while (Report::where('tiket_no', $tiket)->exists());

        return $tiket;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kategori_id' => 'required|exists:report_categories,id',
            'judul'       => 'required|string|max:255',
            'deskripsi'   => 'required|string|max:2000',
            'lat'         => 'required|numeric|between:-90,90',
            'lng'         => 'required|numeric|between:-180,180',
            'alamat'      => 'nullable|string|max:255',
            'foto'        => 'nullable|image|max:4096',
            'images'      => 'nullable|array|max:5',
            'images.*'    => 'image|max:4096',
        ]);

        $user = $request->user();

        $report = Report::create([
            'pelapor_id' => $user->id,
            'kategori_id' => $data['kategori_id'],
            'tiket_no'   => $this->tiketUnik(),
            'judul'      => $data['judul'],
            'deskripsi'  => $data['deskripsi'],
            'lat'        => $data['lat'],
            'lng'        => $data['lng'],
            'alamat'     => $data['alamat'] ?? null,
            'banjar_id'  => $user->banjar_id,
            'status'     => 'menunggu',
        ]);

        if ($request->hasFile('foto')) {
            $report->update(['foto' => $request->file('foto')->store('reports', 'public')]);
        }
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $report->images()->create(['path' => $img->store('reports', 'public')]);
            }
        }

        return response()->json([
            'message' => 'Laporan berhasil dikirim.',
            'data'    => $this->reportPayload($report->fresh('images', 'kategori')),
        ], 201);
    }

    public function index(Request $request)
    {
        $q = Report::where('pelapor_id', $request->user()->id)->with('kategori')->latest();

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        $data = $q->paginate(12)->through(fn ($r) => [
            'id' => $r->id, 'tiket_no' => $r->tiket_no, 'judul' => $r->judul,
            'kategori' => $r->kategori?->nama, 'status' => $r->status, 'alamat' => $r->alamat,
            'foto' => $r->foto ? asset('storage/' . $r->foto) : null,
            'tanggal' => $r->created_at?->toIso8601String(),
        ]);

        return response()->json($data);
    }

    public function show(Request $request, Report $report)
    {
        abort_unless($report->pelapor_id === $request->user()->id, 403);
        $report->load('kategori', 'images', 'progress');

        return response()->json(['data' => $this->reportPayload($report, true)]);
    }

    private function reportPayload(Report $report, bool $lengkap = false): array
    {
        $data = [
            'id' => $report->id, 'tiket_no' => $report->tiket_no, 'judul' => $report->judul,
            'deskripsi' => $report->deskripsi, 'kategori' => $report->kategori?->nama,
            'status' => $report->status, 'alamat' => $report->alamat,
            'lat' => $report->lat, 'lng' => $report->lng,
            'foto' => $report->foto ? asset('storage/' . $report->foto) : null,
            'bukti' => $report->relationLoaded('images')
                ? $report->images->map(fn ($im) => asset('storage/' . $im->path))->values()
                : [],
            'tanggal' => $report->created_at?->toIso8601String(),
        ];

        if ($lengkap && $report->relationLoaded('progress')) {
            $data['progress'] = $report->progress->sortBy('created_at')->map(fn ($p) => [
                'status' => $p->status_progress, 'catatan' => $p->catatan,
                'tanggal' => $p->created_at?->toIso8601String(),
            ])->values();
        }

        return $data;
    }
}