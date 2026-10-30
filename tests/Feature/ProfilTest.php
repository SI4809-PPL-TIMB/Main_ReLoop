<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->user = User::factory()->create([
            'name'  => 'Akun Pengguna Asli',
            'email' => 'pengguna@example.com',
            'phone' => '081234567890',
        ]);
    }

    /**
     * Helper membuat dummy file gambar PNG tanpa dependensi ekstensi PHP GD.
     */
    private function createFakeImage(string $name = 'avatar.png', int $sizeInKilobytes = 50): UploadedFile
    {
        // 1x1 valid PNG binary
        $pngHeader = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $padding = $sizeInKilobytes > 0 ? str_repeat("\0", max(0, ($sizeInKilobytes * 1024) - strlen($pngHeader))) : '';

        return UploadedFile::fake()->createWithContent($name, $pngHeader . $padding);
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/profil')->assertRedirect('/masuk');
        $this->get('/profil/ubah')->assertRedirect('/masuk');
        $this->put('/profil', ['name' => 'Nama Baru'])->assertRedirect('/masuk');
        $this->post('/profil/avatar')->assertRedirect('/masuk');
        $this->delete('/profil/avatar')->assertRedirect('/masuk');
    }

    public function test_halaman_profil_dapat_diakses_oleh_pengguna_login(): void
    {
        $response = $this->actingAs($this->user)->get('/profil');

        $response->assertOk();
        $response->assertSee('Akun Pengguna Asli');
        $response->assertSee('pengguna@example.com');
        $response->assertSee('081234567890');
    }

    public function test_halaman_form_ubah_profil_dapat_diakses_oleh_pengguna_login(): void
    {
        $response = $this->actingAs($this->user)->get('/profil/ubah');

        $response->assertOk();
        $response->assertSee('Ubah Profil');
        $response->assertSee('Akun Pengguna Asli');
        $response->assertSee('Informasi Akun');
    }

    public function test_pengguna_dapat_memperbarui_nama_bio_dan_nomor_hp(): void
    {
        $response = $this->actingAs($this->user)->put('/profil', [
            'name'  => 'Nama Setelah Diedit',
            'phone' => '089876543210',
            'bio'   => 'Suka jual botol kaca dan perabot.',
        ]);

        $response->assertRedirect(route('account'));
        $response->assertSessionHas('sukses', 'Profil berhasil diperbarui.');

        $this->assertDatabaseHas('users', [
            'id'    => $this->user->id,
            'name'  => 'Nama Setelah Diedit',
            'phone' => '089876543210',
        ]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $this->user->id,
            'bio'     => 'Suka jual botol kaca dan perabot.',
        ]);
    }

    public function test_validasi_nama_wajib_dan_maksimal_150_karakter(): void
    {
        $responseKosong = $this->actingAs($this->user)->put('/profil', [
            'name' => '',
        ]);
        $responseKosong->assertSessionHasErrors(['name' => 'Nama tampilan wajib diisi.']);

        $responsePanjang = $this->actingAs($this->user)->put('/profil', [
            'name' => str_repeat('A', 151),
        ]);
        $responsePanjang->assertSessionHasErrors(['name' => 'Nama tampilan tidak boleh lebih dari 150 karakter.']);
    }

    public function test_nomor_hp_harus_unik_kecuali_milik_sendiri(): void
    {
        User::factory()->create([
            'email' => 'lain@example.com',
            'phone' => '081111111111',
        ]);

        // Coba pakai nomor akun lain -> harus error
        $responseDuplikat = $this->actingAs($this->user)->put('/profil', [
            'name'  => 'Akun Pengguna Asli',
            'phone' => '081111111111',
        ]);
        $responseDuplikat->assertSessionHasErrors(['phone' => 'Nomor HP ini sudah digunakan akun lain.']);

        // Simpan nomor milik sendiri -> harus berhasil tanpa error unique
        $responseSendiri = $this->actingAs($this->user)->put('/profil', [
            'name'  => 'Akun Pengguna Asli Baru',
            'phone' => '081234567890',
        ]);
        $responseSendiri->assertRedirect(route('account'));
        $responseSendiri->assertSessionHasNoErrors();
    }

    public function test_nomor_hp_hanya_boleh_berisi_angka(): void
    {
        $response = $this->actingAs($this->user)->put('/profil', [
            'name'  => 'Akun Pengguna Asli',
            'phone' => 'ssss',
        ]);

        $response->assertSessionHasErrors(['phone' => 'Nomor HP hanya boleh berisi angka.']);
    }

    public function test_nomor_hp_wajib_berjumlah_11_sampai_12_angka(): void
    {
        // Kurang dari 11 angka (10 angka)
        $responseKurang = $this->actingAs($this->user)->put('/profil', [
            'name'  => 'Akun Pengguna Asli',
            'phone' => '0812345678',
        ]);
        $responseKurang->assertSessionHasErrors(['phone' => 'Nomor HP harus berjumlah 11 sampai 12 angka.']);

        // Lebih dari 12 angka (13 angka)
        $responseLebih = $this->actingAs($this->user)->put('/profil', [
            'name'  => 'Akun Pengguna Asli',
            'phone' => '0812345678901',
        ]);
        $responseLebih->assertSessionHasErrors(['phone' => 'Nomor HP harus berjumlah 11 sampai 12 angka.']);

        // 11 angka -> valid
        $response11 = $this->actingAs($this->user)->put('/profil', [
            'name'  => 'Akun Pengguna Asli',
            'phone' => '08123456789',
        ]);
        $response11->assertSessionHasNoErrors();

        // 12 angka -> valid
        $response12 = $this->actingAs($this->user)->put('/profil', [
            'name'  => 'Akun Pengguna Asli',
            'phone' => '081234567890',
        ]);
        $response12->assertSessionHasNoErrors();
    }

    public function test_pengguna_dapat_mengunggah_foto_profil(): void
    {
        Storage::fake('public');

        $file = $this->createFakeImage('avatar.png', 50);

        $response = $this->actingAs($this->user)->post('/profil/avatar', [
            'avatar' => $file,
        ]);

        $response->assertRedirect(route('account'));
        $response->assertSessionHas('sukses', 'Foto profil berhasil diperbarui.');

        $this->user->refresh();
        $this->assertNotNull($this->user->profile);
        $this->assertNotNull($this->user->profile->avatar);

        Storage::disk('public')->assertExists($this->user->profile->avatar);
    }

    public function test_unggah_foto_profil_baru_menghapus_foto_lama(): void
    {
        Storage::fake('public');

        $fileLama = $this->createFakeImage('lama.png', 50);
        $this->actingAs($this->user)->post('/profil/avatar', ['avatar' => $fileLama]);

        $this->user->refresh();
        $pathLama = $this->user->profile->avatar;
        Storage::disk('public')->assertExists($pathLama);

        // Upload foto baru
        $fileBaru = $this->createFakeImage('baru.png', 50);
        $this->actingAs($this->user)->post('/profil/avatar', ['avatar' => $fileBaru]);

        $this->user->refresh();
        $pathBaru = $this->user->profile->avatar;

        $this->assertNotEquals($pathLama, $pathBaru);
        Storage::disk('public')->assertMissing($pathLama);
        Storage::disk('public')->assertExists($pathBaru);
    }

    public function test_validasi_format_dan_ukuran_foto_profil(): void
    {
        Storage::fake('public');

        // File non-gambar (txt/pdf)
        $fileDokumen = UploadedFile::fake()->create('dokumen.pdf', 50, 'application/pdf');
        $responseDokumen = $this->actingAs($this->user)->post('/profil/avatar', [
            'avatar' => $fileDokumen,
        ]);
        $responseDokumen->assertSessionHasErrors('avatar');

        // File melebihi batas 2048 KB (3000 KB)
        $fileTerlaluBesar = $this->createFakeImage('besar.png', 3000);
        $responseBesar = $this->actingAs($this->user)->post('/profil/avatar', [
            'avatar' => $fileTerlaluBesar,
        ]);
        $responseBesar->assertSessionHasErrors(['avatar' => 'Ukuran foto tidak boleh lebih dari 2 MB.']);
    }

    public function test_pengguna_dapat_menghapus_foto_profil(): void
    {
        Storage::fake('public');

        $file = $this->createFakeImage('avatar.png', 50);
        $this->actingAs($this->user)->post('/profil/avatar', ['avatar' => $file]);

        $this->user->refresh();
        $pathAvatar = $this->user->profile->avatar;
        Storage::disk('public')->assertExists($pathAvatar);

        // Hapus foto profil
        $response = $this->actingAs($this->user)->delete('/profil/avatar');

        $response->assertRedirect(route('account'));
        $response->assertSessionHas('sukses', 'Foto profil berhasil dihapus.');

        $this->user->refresh();
        $this->assertNull($this->user->profile->avatar);
        Storage::disk('public')->assertMissing($pathAvatar);
    }

    public function test_avatar_inisial_ditampilkan_saat_belum_ada_foto_dan_setelah_foto_dihapus(): void
    {
        // User 'Akun Pengguna Asli' menghasilkan inisial 'AP'
        $response = $this->actingAs($this->user)->get('/profil');
        $response->assertOk();
        $response->assertSee('AP');

        $responseEdit = $this->get('/profil/ubah');
        $responseEdit->assertOk();
        $responseEdit->assertSee('AP');
    }
}
