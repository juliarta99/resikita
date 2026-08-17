<?php

declare(strict_types=1);

use App\Enums\PeranChat;
use App\Enums\Role;
use App\Enums\SumberInput;
use App\Models\ChatPesan;
use App\Models\ChatSesi;
use App\Models\User;
use App\Models\Wilayah;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Asisten literasi lingkungan lewat kanal API (CLAUDE.md 10.2).
 *
 * Uji yang paling penting di berkas ini bukan soal balasan tersimpan,
 * melainkan soal isi prompt: tidak boleh ada nama daerah tertentu di
 * instruksi dasar. Kalau satu daerah ikut tertanam di sana, seluruh
 * pengguna di luar daerah itu menerima saran yang tidak berlaku di
 * tempat tinggalnya, dan tidak ada yang menyadarinya sampai ada yang
 * mengeluh.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->user = User::factory()->withRole(Role::Masyarakat)->create();
});

/** Palsukan satu balasan chat Gemini. */
function asistenMenjawab(string $teks = 'Sampah dapur sebagian besar sisa makanan, dan itu bisa dikompos.'): void
{
    Http::fake([
        '*generativelanguage*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [['text' => $teks]]],
                'finishReason' => 'STOP',
            ]],
        ]),
    ]);
}

/** Instruksi sistem yang benar-benar dikirim pada permintaan terakhir. */
function instruksiTerkirim(): string
{
    $instruksi = '';

    Http::assertSent(function (Request $request) use (&$instruksi): bool {
        $instruksi = $request->data()['systemInstruction']['parts'][0]['text'] ?? '';

        return true;
    });

    return $instruksi;
}

it('membuat sesi otomatis pada pesan pertama', function (): void {
    asistenMenjawab();

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/chatbot/pesan', ['pesan' => 'Bagaimana cara mengolah sampah dapur?']);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.pesan.role', 'model');

    $sesi = ChatSesi::sole();

    expect($sesi->user_id)->toBe($this->user->id)
        // Judul diambil dari pertanyaan pertama, bukan tetap "Percakapan Baru".
        ->and($sesi->judul)->toContain('sampah dapur')
        ->and($sesi->pesan()->count())->toBe(2);
});

it('menyimpan riwayat hanya di tabel pesan, bukan sebagai salinan kedua', function (): void {
    asistenMenjawab();

    $this->actingAs($this->user)
        ->postJson('/api/v1/chatbot/pesan', ['pesan' => 'Apa itu TPS3R?', 'sumber_input' => 'suara'])
        ->assertOk();

    $pertanyaan = ChatPesan::where('role', PeranChat::User)->sole();

    expect($pertanyaan->sumber_input)->toBe(SumberInput::Suara)
        ->and(ChatPesan::where('role', PeranChat::Model)->sole()->model_version)
        ->toBe(config('services.gemini.model'));
});

it('tidak menanam nama daerah tertentu di instruksi dasar', function (): void {
    asistenMenjawab();

    $this->actingAs($this->user)
        ->postJson('/api/v1/chatbot/pesan', ['pesan' => 'Bagaimana memilah sampah rumah?'])
        ->assertOk();

    $instruksi = strtolower(instruksiTerkirim());

    // Pengguna ini tidak punya wilayah, jadi tidak boleh ada satu pun
    // daerah yang disebut sebagai konteksnya.
    expect($instruksi)
        ->not->toContain('badung')
        ->not->toContain('kabupaten badung')
        ->and($instruksi)->toContain('wilayah pengguna tidak diketahui');

    // Nama daerah pada daftar kearifan lokal boleh ada, tapi harus
    // muncul setara, tidak sebagai rujukan bawaan.
    expect($instruksi)->toContain('sasi di maluku')
        ->and($instruksi)->toContain('lubuk larangan');
});

it('menyisipkan wilayah pengguna sebagai konteks sesi', function (): void {
    $provinsi = Wilayah::factory()->create(['nama' => 'Sulawesi Selatan']);
    $this->user->forceFill(['wilayah_id' => $provinsi->id])->save();

    asistenMenjawab();

    $this->actingAs($this->user)
        ->postJson('/api/v1/chatbot/pesan', ['pesan' => 'Di mana saya bisa menyetor sampah?'])
        ->assertOk();

    expect(instruksiTerkirim())->toContain('Provinsi Sulawesi Selatan');

    // Konteksnya dikunci pada sesi, bukan dibaca ulang tiap giliran,
    // supaya jawaban lama tetap bisa dijelaskan.
    expect(ChatSesi::sole()->wilayah_konteks_id)->toBe($provinsi->id);
});

it('menyebut Jakstranas sebagai Peraturan Presiden, bukan Peraturan Pemerintah', function (): void {
    asistenMenjawab();

    $this->actingAs($this->user)
        ->postJson('/api/v1/chatbot/pesan', ['pesan' => 'Apa dasar hukum pengelolaan sampah?'])
        ->assertOk();

    expect(instruksiTerkirim())
        ->toContain('Peraturan Presiden Nomor 97 Tahun 2017')
        ->not->toContain('Peraturan Pemerintah Nomor 97');
});

it('mengirim ulang giliran sebelumnya sebagai konteks percakapan', function (): void {
    asistenMenjawab();

    $this->actingAs($this->user)
        ->postJson('/api/v1/chatbot/pesan', ['pesan' => 'Apa itu kompos?'])
        ->assertOk();

    $sesiId = ChatSesi::sole()->id;

    asistenMenjawab('Bahan yang bisa dikompos antara lain sisa sayur dan daun kering.');

    $this->actingAs($this->user)
        ->postJson('/api/v1/chatbot/pesan', ['pesan' => 'Bahan apa saja yang cocok?', 'sesi_id' => $sesiId])
        ->assertOk();

    Http::assertSent(function (Request $request): bool {
        $contents = $request->data()['contents'];

        // Giliran pertama ikut terkirim, dan urutannya berselang-seling
        // pengguna lalu model, Gemini menolak riwayat yang tidak begitu.
        return count($contents) === 3
            && $contents[0]['role'] === 'user'
            && $contents[1]['role'] === 'model'
            && $contents[2]['role'] === 'user'
            && str_contains($contents[0]['parts'][0]['text'], 'Apa itu kompos?');
    });
});

it('tidak menyimpan pertanyaan ketika layanan AI gagal', function (): void {
    Http::fake(['*generativelanguage*' => Http::response(status: 500)]);

    $this->actingAs($this->user)
        ->postJson('/api/v1/chatbot/pesan', ['pesan' => 'Bagaimana cara memilah sampah?'])
        ->assertStatus(503)
        ->assertJsonPath('success', false);

    expect(ChatPesan::count())->toBe(0);
});

it('menolak membuka sesi milik pengguna lain', function (): void {
    asistenMenjawab();

    $this->actingAs($this->user)
        ->postJson('/api/v1/chatbot/pesan', ['pesan' => 'Apa itu bank sampah?'])
        ->assertOk();

    $sesiId = ChatSesi::sole()->id;
    $orangLain = User::factory()->withRole(Role::Masyarakat)->create();

    $this->actingAs($orangLain)->getJson("/api/v1/chatbot/sesi/{$sesiId}")->assertForbidden();
    $this->actingAs($orangLain)->deleteJson("/api/v1/chatbot/sesi/{$sesiId}")->assertForbidden();

    $this->actingAs($orangLain)
        ->postJson('/api/v1/chatbot/pesan', ['pesan' => 'Lanjutkan percakapan itu.', 'sesi_id' => $sesiId])
        ->assertForbidden();
});

it('mencatat balasan yang sudah dibacakan pembaca suara', function (): void {
    asistenMenjawab();

    $this->actingAs($this->user)
        ->postJson('/api/v1/chatbot/pesan', ['pesan' => 'Apa itu ekonomi sirkular?'])
        ->assertOk();

    $balasan = ChatPesan::where('role', PeranChat::Model)->sole();

    $this->actingAs($this->user)
        ->patchJson("/api/v1/chatbot/pesan/{$balasan->id}/dibacakan")
        ->assertOk()
        ->assertJsonPath('data.dibacakan', true);
});

it('menyesuaikan saran pertanyaan dengan wilayah pengguna', function (): void {
    Http::fake();

    $provinsi = Wilayah::factory()->create(['nama' => 'Nusa Tenggara Timur']);
    $this->user->forceFill(['wilayah_id' => $provinsi->id])->save();

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/chatbot/saran-pertanyaan')
        ->assertOk();

    expect($response->json('data.saran.0'))->toContain('Provinsi Nusa Tenggara Timur');

    // Pemantik tidak boleh memanggil model, biayanya nyata dan
    // gunanya nol untuk sekadar mengisi layar kosong.
    Http::assertNothingSent();
});

it('menolak permintaan tanpa token', function (): void {
    $this->postJson('/api/v1/chatbot/pesan', ['pesan' => 'Halo'])
        ->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('menolak pesan kosong dengan galat validasi', function (): void {
    $this->actingAs($this->user)
        ->postJson('/api/v1/chatbot/pesan', ['pesan' => ''])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['pesan']]);
});
