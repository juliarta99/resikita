<?php

namespace App\Livewire\Umkm;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.umkm')]
class ProductManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $showForm = false;
    public bool $showDelete = false;
    public ?int $deleteId = null;

    public ?int $editingId = null;
    public string $nama = '';
    public string $kategori_id = '';
    public string $deskripsi = '';
    public string $harga = '';
    public string $stok = '';
    public string $berat = '';
    public bool $is_active = true;

    public array $newImages = [];
    public $existingImages = [];

    protected function rules(): array
    {
        return [
            'nama'        => 'required|string|max:255',
            'kategori_id' => 'required|exists:product_categories,id',
            'deskripsi'   => 'nullable|string|max:2000',
            'harga'       => 'required|numeric|min:0',
            'stok'        => 'required|integer|min:0',
            'berat'       => 'required|integer|min:0',
            'is_active'   => 'boolean',
            'newImages'   => 'nullable|array|max:5',
            'newImages.*' => 'image|max:2048',
        ];
    }

    private function umkmId(): int
    {
        return Auth::user()->umkm_id;
    }

    public function tambah()
    {
        $this->batal();
        $this->showForm = true;
    }

    public function edit(int $id)
    {
        $p = Product::where('umkm_id', $this->umkmId())->with('images')->findOrFail($id);
        $this->editingId = $p->id;
        $this->nama = $p->nama;
        $this->kategori_id = (string) $p->kategori_id;
        $this->deskripsi = $p->deskripsi ?? '';
        $this->harga = (string) $p->harga;
        $this->stok = (string) $p->stok;
        $this->berat = (string) $p->berat;
        $this->is_active = (bool) $p->is_active;
        $this->existingImages = $p->images->map(fn ($im) => ['id' => $im->id, 'path' => $im->path])->all();
        $this->newImages = [];
        $this->showForm = true;
    }

    public function hapusGambar(int $imageId)
    {
        $img = ProductImage::whereHas('product', fn ($q) => $q->where('umkm_id', $this->umkmId()))->find($imageId);
        if ($img) {
            Storage::disk('public')->delete($img->path);
            $img->delete();
            $this->existingImages = array_values(array_filter($this->existingImages, fn ($im) => $im['id'] !== $imageId));
        }
    }

    public function simpan()
    {
        $data = $this->validate();

        $attrs = [
            'kategori_id' => $data['kategori_id'],
            'nama'        => $data['nama'],
            'deskripsi'   => $data['deskripsi'] ?? null,
            'harga'       => $data['harga'],
            'stok'        => $data['stok'],
            'berat'       => $data['berat'],
            'is_active'   => $data['is_active'],
        ];

        if ($this->editingId) {
            $product = Product::where('umkm_id', $this->umkmId())->findOrFail($this->editingId);
            $product->update($attrs);
            $pesan = 'Produk diperbarui.';
        } else {
            $product = Product::create($attrs + ['umkm_id' => $this->umkmId()]);
            $pesan = 'Produk ditambahkan.';
        }

        foreach ($this->newImages as $img) {
            $path = $img->store('products', 'public');
            ProductImage::create(['product_id' => $product->id, 'path' => $path]);
        }

        $this->batal();
        session()->flash('ok', $pesan);
    }

    public function konfirmHapus(int $id)
    {
        $this->deleteId = $id;
        $this->showDelete = true;
    }

    public function hapus()
    {
        $this->showDelete = false;
        $product = Product::where('umkm_id', $this->umkmId())->with('images')->find($this->deleteId);
        $this->deleteId = null;

        if (! $product) {
            return;
        }

        foreach ($product->images as $im) {
            Storage::disk('public')->delete($im->path);
        }
        $product->delete();

        session()->flash('ok', 'Produk dihapus.');
    }

    public function batal()
    {
        $this->reset('showForm', 'editingId', 'nama', 'kategori_id', 'deskripsi', 'harga', 'stok', 'berat', 'is_active', 'newImages', 'existingImages');
    }

    public function render()
    {
        return view('livewire.umkm.product-manager', [
            'kategoriList' => ProductCategory::orderBy('nama')->get(),
            'produk'       => Product::where('umkm_id', $this->umkmId())->with('images')->latest()->paginate(10),
        ]);
    }
}