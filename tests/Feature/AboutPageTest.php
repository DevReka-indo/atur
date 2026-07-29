<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use CreatesProjectTemplateTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createProjectTemplateTestSchema();
    }

    public function test_authenticated_about_page_keeps_existing_route_and_shows_professional_application_information(): void
    {
        $user = User::factory()->create();

        $this->assertSame('/settings/about', route('settings.about', absolute: false));

        $this->actingAs($user)
            ->get(route('settings.about'))
            ->assertOk()
            ->assertViewIs('about.index')
            ->assertSee('Tentang Aplikasi')
            ->assertSee('ATUR')
            ->assertSee(config('atur.tagline'))
            ->assertSee(config('atur.developer'))
            ->assertSee('Versi '.config('atur.version'))
            ->assertSee(config('atur.environment_label'))
            ->assertSee(config('atur.release_year'))
            ->assertSee(config('atur.license'))
            ->assertSee('Project Discussions')
            ->assertSee('Workspace Chat')
            ->assertSee('Workload / Overload Monitoring')
            ->assertDontSee('>Discussion<', false)
            ->assertSee('mailto:'.config('atur.support_email'), false);
    }

    public function test_about_page_uses_privacy_summary_and_accessible_document_modal_instead_of_accordion(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('settings.about'))
            ->assertOk()
            ->assertSee('Privasi &amp; Legal', false)
            ->assertSee('Lihat Kebijakan Privasi')
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('aria-labelledby="privacy-modal-title"', false)
            ->assertSee('aria-label="Tutup Kebijakan Privasi"', false)
            ->assertSee('data-privacy-section-select', false)
            ->assertSee('lg:grid-cols-[16rem_minmax(0,1fr)]', false)
            ->assertSee(config('atur.privacy_version'))
            ->assertSee(config('atur.privacy_effective_date'));

        $content = $response->getContent();

        $this->assertStringNotContainsString('x-data="{ open:', $content);
        $this->assertStringNotContainsString('x-show="open"', $content);
        $this->assertSame(1, substr_count($content, 'Kebijakan ini mengikat seluruh Pengguna sistem ATUR'));
        $this->assertSame(1, substr_count($content, 'Perubahan signifikan akan diberitahukan'));
    }

    public function test_public_privacy_policy_page_is_available_and_contains_every_existing_section(): void
    {
        $this->assertTrue(Route::has('privacy-policy.show'));
        $this->assertSame('/privacy-policy', route('privacy-policy.show', absolute: false));

        $this->get(route('privacy-policy.show'))
            ->assertOk()
            ->assertViewIs('legal.privacy-policy')
            ->assertSeeTextInOrder([
                '1. Ketentuan Umum & Ruang Lingkup',
                '2. Data Pribadi yang Dikumpulkan',
                '3. Tujuan Penggunaan Data',
                '4. Keamanan & Perlindungan Data',
                '5. Hak-Hak Pengguna',
                '6. Retensi Data & Penghapusan',
                '7. Perubahan Kebijakan',
            ])
            ->assertSee('UU No. 27 Tahun 2022')
            ->assertSee('90 hari')
            ->assertSee('14 hari')
            ->assertSee(config('atur.privacy_version'))
            ->assertSee(config('atur.privacy_effective_date'))
            ->assertSee('mailto:'.config('atur.support_email'), false)
            ->assertSee('sm:p-8', false)
            ->assertSee('lg:grid-cols-[16rem_minmax(0,1fr)]', false);
    }

    public function test_privacy_content_has_one_source_and_about_javascript_uses_safe_accessible_modal_controls(): void
    {
        $privacyPartial = file_get_contents(resource_path('views/legal/partials/_privacy-content.blade.php'));
        $privacyModal = file_get_contents(resource_path('views/about/partials/_privacy-modal.blade.php'));
        $privacyPage = file_get_contents(resource_path('views/legal/privacy-policy.blade.php'));
        $aboutScript = file_get_contents(resource_path('js/about.js'));
        $aboutViews = collect([
            resource_path('views/about'),
            resource_path('views/legal'),
        ])->flatMap(fn (string $directory): array => [
            ...(glob("{$directory}/*.blade.php") ?: []),
            ...(glob("{$directory}/**/*.blade.php") ?: []),
        ])
            ->map(fn (string $file): string => file_get_contents($file))
            ->implode("\n");

        $this->assertStringContainsString('Sistem tidak mengumpulkan data keuangan', $privacyPartial);
        $this->assertStringNotContainsString('Sistem tidak mengumpulkan data keuangan', $privacyModal);
        $this->assertStringNotContainsString('Sistem tidak mengumpulkan data keuangan', $privacyPage);
        $this->assertSame(1, substr_count($privacyModal, "@include('legal.partials._privacy-content')"));
        $this->assertSame(1, substr_count($privacyPage, "@include('legal.partials._privacy-content')"));
        $this->assertStringNotContainsString('{!!', $aboutViews);
        $this->assertDoesNotMatchRegularExpression('/\\b(?:DB|[A-Z][A-Za-z]+)::(?:query|where|get|all)\\s*\\(/', $aboutViews);
        $this->assertStringContainsString("event.key === 'Escape'", $aboutScript);
        $this->assertStringContainsString('modalTrigger?.focus()', $aboutScript);
        $this->assertStringContainsString("event.key !== 'Tab'", $aboutScript);
        $this->assertStringNotContainsString('innerHTML', $aboutScript);
    }
}
