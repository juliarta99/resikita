<?php

declare(strict_types=1);

use App\Enums\PeranChat;
use App\Enums\Role;
use App\Enums\SumberInput;
use App\Livewire\Asisten;
use App\Models\BankSampah;
use App\Models\ChatPesan;
use App\Models\ChatSesi;
use App\Models\Umkm;
use App\Models\User;
use App\Models\Wilayah;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Asisten literasi lingkungan di panel web.
 *
 * Komponen yang sama dipakai bank sampah dan UMKM. Yang diuji: jawaban
 * tersimpan lewat Service yang sama dengan kanal mobile, masukan suara
 * tercatat sebagai `sumber_input = suara`, dan riwayat percakapan tidak
 * bisa disentuh pengguna lain.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->pengelola = User::factory()->withRole(Role::BankSampah)->create([
        'bank_sampah_id' => BankSampah::factory()->create()->id,
    ]);
});

function asistenMembalas(string $teks = 'Sampah dapur bisa dikompos di rumah.'): void
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

it('membuka halaman asisten untuk bank sampah dan UMKM', function (): void {
    $this->actingAs($this->pengelola)->get('/bank-sampah/asisten')->assertOk();

    $umkm = User::factory()->withRole(Role::Umkm)->create([
        'umkm_id' => Umkm::factory()->create()->id,
    ]);

    $this->actingAs($umkm)->get('/umkm/asisten')->assertOk();
});

it('menolak role tanpa permission chatbot', function (): void {
    $kepalaDesa = User::factory()->withRole(Role::KepalaDesa)->create([
        'wilayah_id' => Wilayah::factory()->create()->id,
    ]);

    // Bukan hanya karena rolenya beda panel: kepala desa memang tidak
    // punya permission chatbot.pakai pada matriks izin.
    $this->actingAs($kepalaDesa)->get('/bank-sampah/asisten')->assertForbidden();
});

it('menyimpan pertanyaan dan jawaban dalam satu sesi', function (): void {
    asistenMembalas();

    Livewire::actingAs($this->pengelola)
        ->test(Asisten::class)
        ->set('pesan', 'Bagaimana cara mengolah sampah dapur?')
        ->call('kirim')
        ->assertSet('pesan', '');

    $sesi = ChatSesi::sole();

    expect($sesi->user_id)->toBe($this->pengelola->id)
        ->and($sesi->pesan()->count())->toBe(2)
        ->and(ChatPesan::where('role', PeranChat::Model)->sole()->model_version)
        ->toBe(config('services.gemini.model'));
});

it('menandai pertanyaan yang didiktekan sebagai masukan suara', function (): void {
    asistenMembalas();

    Livewire::actingAs($this->pengelola)
        ->test(Asisten::class)
        ->set('pesan', 'Apa itu TPS3R?')
        ->set('lewatSuara', true)
        ->call('kirim');

    expect(ChatPesan::where('role', PeranChat::User)->sole()->sumber_input)
        ->toBe(SumberInput::Suara);
});

it('mengembalikan penanda suara ke ketik setelah terkirim', function (): void {
    asistenMembalas();

    $komponen = Livewire::actingAs($this->pengelola)
        ->test(Asisten::class)
        ->set('pesan', 'Pertanyaan pertama lewat suara.')
        ->set('lewatSuara', true)
        ->call('kirim')
        ->assertSet('lewatSuara', false);

    asistenMembalas('Jawaban kedua.');

    $komponen->set('pesan', 'Pertanyaan kedua diketik.')->call('kirim');

    $sumber = ChatPesan::where('role', PeranChat::User)
        ->orderBy('id')
        ->pluck('sumber_input');

    expect($sumber->all())->toBe([SumberInput::Suara, SumberInput::Ketik]);
});

it('tidak menyimpan pertanyaan ketika layanan AI gagal', function (): void {
    Http::fake(['*generativelanguage*' => Http::response(status: 503)]);

    Livewire::actingAs($this->pengelola)
        ->test(Asisten::class)
        ->set('pesan', 'Bagaimana cara memilah sampah?')
        ->call('kirim')
        ->assertDispatched('pesan');

    expect(ChatPesan::count())->toBe(0);
});

it('menolak membuka dan menghapus sesi milik pengguna lain', function (): void {
    asistenMembalas();

    Livewire::actingAs($this->pengelola)
        ->test(Asisten::class)
        ->set('pesan', 'Apa itu bank sampah unit?')
        ->call('kirim');

    $sesiId = ChatSesi::sole()->id;

    $orangLain = User::factory()->withRole(Role::BankSampah)->create([
        'bank_sampah_id' => BankSampah::factory()->create()->id,
    ]);

    // Sesi orang lain tidak pernah termuat: id di URL diabaikan begitu
    // kepemilikannya tidak cocok.
    Livewire::actingAs($orangLain)
        ->test(Asisten::class, ['sesiId' => $sesiId])
        ->assertSet('sesiId', null);

    Livewire::actingAs($orangLain)
        ->test(Asisten::class)
        ->call('hapusSesi', $sesiId)
        ->assertForbidden();

    expect(ChatSesi::count())->toBe(1);
});

it('menampilkan saran pertanyaan tanpa memanggil model', function (): void {
    Http::fake();

    Livewire::actingAs($this->pengelola)
        ->test(Asisten::class)
        ->assertSee('Mulai dari salah satu ini');

    Http::assertNothingSent();
});
