<?php

namespace Database\Seeders\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

trait DownloadsAvatars
{
    /**
     * Download a photo from $url and save it as the user's avatar on the public disk.
     *
     * Skips the download when the user already has an avatar file. Failures (e.g. no
     * internet) only print a warning, so the user simply keeps the initials avatar.
     */
    protected function downloadAvatar(User $user, string $url): void
    {
        $disk = Storage::disk('public');

        if ($user->avatar && $disk->exists($user->avatar)) {
            return;
        }

        try {
            $response = Http::timeout(15)->retry(2, 300, throw: false)->get($url);

            if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                $this->command?->warn("  Could not download a photo for {$user->name} (HTTP {$response->status()}).");

                return;
            }

            $path = 'avatars/seed-'.Str::slug($user->email).'.jpg';
            $disk->put($path, $response->body());

            $user->forceFill(['avatar' => $path])->save();
        } catch (Throwable $e) {
            $this->command?->warn("  Could not download a photo for {$user->name}: {$e->getMessage()}");
        }
    }
}
