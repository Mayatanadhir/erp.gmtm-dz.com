<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageFileDeliveryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Public storage file can be retrieved via the fallback route.
     */
    public function test_can_retrieve_file_from_public_storage(): void
    {
        $testDir = storage_path('app/public/testing');
        File::ensureDirectoryExists($testDir);

        $filePath = $testDir.'/test-avatar.webp';
        File::put($filePath, 'fake-image-content');

        try {
            $response = $this->get('/storage/testing/test-avatar.webp');

            $response->assertOk();
            $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
        } finally {
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
            if (File::isDirectory($testDir)) {
                File::deleteDirectory($testDir);
            }
        }
    }

    /**
     * Non-existent file returns 404.
     */
    public function test_non_existent_file_returns_404(): void
    {
        $response = $this->get('/storage/testing/non-existent-image-12345.webp');

        $response->assertNotFound();
    }

    /**
     * Path traversal attempts are blocked and return 404.
     */
    public function test_path_traversal_is_blocked(): void
    {
        $response = $this->get('/storage/../../.env');

        $response->assertNotFound();
    }

    /**
     * User model returns asset URL for profile photo.
     */
    public function test_user_profile_photo_url_returns_correct_asset_path(): void
    {
        $user = User::factory()->make([
            'profile_photo_path' => 'photos/avatar_123.webp',
        ]);

        $this->assertNotNull($user->profile_photo_url);
        $this->assertStringContainsString('/storage/photos/avatar_123.webp', $user->profile_photo_url);
    }
}
