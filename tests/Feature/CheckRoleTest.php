<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CheckRoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth', 'role:manajer'])
            ->get('/uji/manajer', fn () => 'ok');

        Route::middleware(['auth', 'role:manajer,kasir'])
            ->get('/uji/staf', fn () => 'ok');
    }

    private function userDengan(string $peran): User
    {
        return new User([
            'nama' => 'Uji',
            'no_telp' => '0800000000',
            'peran' => $peran,
        ]);
    }

    public function test_tanpa_login_ditolak(): void
    {
        $this->getJson('/uji/manajer')->assertUnauthorized();
    }

    public function test_kasir_tidak_boleh_masuk_route_manajer(): void
    {
        $this->actingAs($this->userDengan('kasir'))
            ->get('/uji/manajer')
            ->assertForbidden();
    }

    public function test_manajer_boleh_masuk_route_manajer(): void
    {
        $this->actingAs($this->userDengan('manajer'))
            ->get('/uji/manajer')
            ->assertOk();
    }

    public function test_route_dua_peran_menerima_manajer_dan_kasir(): void
    {
        $this->actingAs($this->userDengan('manajer'))
            ->get('/uji/staf')->assertOk();

        $this->actingAs($this->userDengan('kasir'))
            ->get('/uji/staf')->assertOk();
    }

    public function test_pelanggan_ditolak_di_route_staf(): void
    {
        $this->actingAs($this->userDengan('pelanggan'))
            ->get('/uji/staf')
            ->assertForbidden();
    }
}