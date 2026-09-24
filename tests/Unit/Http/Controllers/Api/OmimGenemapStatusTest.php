<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OmimGenemapStatusTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        DB::table('app_states')->where('id', 1)->orWhere('name', 'last_genemap_download')->delete();
        DB::table('app_states')->insert([
            'id' => 1, 'name' => 'last_genemap_download', 'type' => 'date', 'value' => null,
        ]);
    }

    public function test_returns_only_the_stored_genemap_download_value(): void
    {
        DB::table('app_states')->where('id', 1)->update([
            'name' => 'last_genemap_download', 'value' => '2026-09-23 12:34:56',
        ]);
        $this->actingAs(factory(User::class)->create(), 'api')
            ->getJson('/api/omim/genemap-status')
            ->assertOk()->assertExactJson(['last_genemap_download' => '2026-09-23 12:34:56']);
    }

    public function test_missing_row_returns_null(): void
    {
        DB::table('app_states')->where('id', 1)->delete();
        $this->actingAs(factory(User::class)->create(), 'api')
            ->getJson('/api/omim/genemap-status')
            ->assertOk()->assertExactJson(['last_genemap_download' => null]);
    }

    public function test_invalid_value_is_read_without_throwing_or_changing_it(): void
    {
        DB::table('app_states')->where('id', 1)->update(['value' => 'not-a-date']);
        $this->actingAs(factory(User::class)->create(), 'api')
            ->getJson('/api/omim/genemap-status')
            ->assertOk()->assertExactJson(['last_genemap_download' => 'not-a-date']);
        $this->assertDatabaseHas('app_states', ['id' => 1, 'value' => 'not-a-date']);
    }

    public function test_does_not_return_an_unrelated_state_at_id_one(): void
    {
        DB::table('app_states')->where('id', 1)->update(['name' => 'unrelated_state']);
        $this->actingAs(factory(User::class)->create(), 'api')
            ->getJson('/api/omim/genemap-status')
            ->assertOk()->assertExactJson(['last_genemap_download' => null]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/omim/genemap-status')->assertUnauthorized();
    }
}
