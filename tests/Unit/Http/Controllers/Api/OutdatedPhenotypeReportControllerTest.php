<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Curation;
use App\ExpertPanel;
use App\Phenotype;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('api')]
#[\PHPUnit\Framework\Attributes\Group('admin-outdated-phenotypes')]
class OutdatedPhenotypeReportControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $programmer;

    public function setUp(): void
    {
        parent::setUp();
        $this->programmer = factory(User::class)->create();
        $this->programmer->assignRole(Role::firstOrCreate(['name' => 'programmer', 'guard_name' => 'web']));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_counts_distinguish_all_outdated_labels_affected_curations_and_labels_in_use(): void
    {
        $before = $this->actingAs($this->programmer, 'api')->getJson('/api/admin/dashboard')->json();
        $first = $this->outdatedPhenotype(980001, 'Dashboard Used One');
        $second = $this->outdatedPhenotype(980002, 'Dashboard Used Two');
        $this->outdatedPhenotype(980003, 'Dashboard Unused');
        $firstCuration = factory(Curation::class)->create();
        $secondCuration = factory(Curation::class)->create();
        $firstCuration->phenotypes()->attach([$first->id, $second->id]);
        $secondCuration->phenotypes()->attach($first->id);

        $response = $this->actingAs($this->programmer, 'api')->getJson('/api/admin/dashboard')->assertOk();
        $response->assertJson([
            'outdated_phenotypes' => $before['outdated_phenotypes'] + 3,
            'affected_curations' => $before['affected_curations'] + 2,
            'outdated_phenotypes_in_use' => $before['outdated_phenotypes_in_use'] + 2,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function phenotype_report_returns_counts_links_and_bounded_server_pagination(): void
    {
        $phenotype = $this->outdatedPhenotype(980011, 'Paginated Outdated Label');
        $curation = factory(Curation::class)->create(['gene_symbol' => 'REPORTGENE']);
        $curation->phenotypes()->attach($phenotype->id);

        $response = $this->actingAs($this->programmer, 'api')
            ->getJson('/api/admin/reports/outdated-phenotypes?per_page=1000')
            ->assertOk()->assertJsonPath('per_page', 100);
        $row = collect($response->json('data'))->firstWhere('id', $phenotype->id);
        $this->assertSame(1, $row['affected_curations']);
        $this->assertSame($curation->id, $row['curations'][0]['id']);
        $this->assertSame('REPORTGENE', $row['curations'][0]['gene_symbol']);

        $this->actingAs($this->programmer, 'api')
            ->getJson('/api/admin/reports/outdated-phenotypes?per_page=0')
            ->assertOk()->assertJsonPath('per_page', 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function curation_report_returns_only_outdated_labels_and_required_relationship_fields(): void
    {
        $panel = factory(ExpertPanel::class)->create(['name' => 'Report Expert Panel']);
        $curation = factory(Curation::class)->create([
            'gene_symbol' => 'CURATIONREPORT',
            'mondo_id' => 'MONDO:0000001',
            'expert_panel_id' => $panel->id,
        ]);
        $outdated = $this->outdatedPhenotype(980021, 'Attached Outdated Label');
        $current = factory(Phenotype::class)->create(['mim_number' => 980022, 'name' => 'Current Label', 'label_obsolete_at' => null]);
        $curation->phenotypes()->attach([$outdated->id, $current->id]);

        $response = $this->actingAs($this->programmer, 'api')
            ->getJson('/api/admin/reports/outdated-curations?per_page=20')->assertOk();
        $row = collect($response->json('data'))->firstWhere('id', $curation->id);
        $this->assertSame('CURATIONREPORT', $row['gene_symbol']);
        $this->assertSame('MONDO:0000001', $row['mondo_id']);
        $this->assertSame('Report Expert Panel', $row['expert_panel']['name']);
        $this->assertSame(['Attached Outdated Label'], collect($row['phenotypes'])->pluck('name')->all());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function csv_exports_preserve_historical_headers_and_include_complete_report_rows(): void
    {
        $phenotype = $this->outdatedPhenotype(980031, 'CSV Outdated Label');
        $curation = factory(Curation::class)->create(['gene_symbol' => 'CSVGENE']);
        $curation->phenotypes()->attach($phenotype->id);

        $phenotypeCsv = $this->actingAs($this->programmer, 'api')
            ->get('/api/admin/reports/outdated-phenotypes/export')->assertOk();
        $this->assertStringContainsString('text/csv', $phenotypeCsv->headers->get('content-type'));
        $phenotypeContent = $phenotypeCsv->streamedContent();
        $this->assertStringContainsString('"Phenotype ID","MIM Number",Name,"Affected Curations Count","Sample Curation Links"', $phenotypeContent);
        $this->assertStringContainsString('CSV Outdated Label', $phenotypeContent);

        $curationCsv = $this->actingAs($this->programmer, 'api')
            ->get('/api/admin/reports/outdated-curations/export')->assertOk();
        $curationContent = $curationCsv->streamedContent();
        $this->assertStringContainsString('"Precuration ID","Curation Link",Gene,"MONDO ID","Expert Panel","Outdated Phenotype Labels"', $curationContent);
        $this->assertStringContainsString('CSVGENE', $curationContent);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_administrators_cannot_access_dashboard_reports_or_exports(): void
    {
        $viewer = factory(User::class)->create();
        foreach ([
            '/api/admin/dashboard',
            '/api/admin/reports/outdated-phenotypes',
            '/api/admin/reports/outdated-curations',
            '/api/admin/reports/outdated-phenotypes/export',
            '/api/admin/reports/outdated-curations/export',
        ] as $url) {
            $this->actingAs($viewer, 'api')->getJson($url)->assertForbidden();
        }
    }

    private function outdatedPhenotype(int $mimNumber, string $name): Phenotype
    {
        return factory(Phenotype::class)->create([
            'mim_number' => $mimNumber,
            'name' => $name,
            'label_obsolete_at' => '2026-01-01 00:00:00',
        ]);
    }
}
