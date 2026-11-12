<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Public profile: photo, name, bio and skills. Contacts only for swap partners.
     */
    public function show(Request $request, User $user): View
    {
        $user->load([
            'offeredSkills' => fn ($query) => $query->orderBy('name'),
            'wantedSkills' => fn ($query) => $query->orderBy('name'),
        ]);

        return view('profile.show', [
            'user' => $user,
            'canSeeContact' => $request->user()->canSeeContactOf($user),
        ]);
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Accept "@handle" or a full link and keep only the handle.
        $request->merge([
            'telegram' => $this->normalizeHandle($request->input('telegram'), ['t.me/', 'telegram.me/']),
            'instagram' => $this->normalizeHandle($request->input('instagram'), ['instagram.com/']),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_avatar' => ['boolean'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-().]{6,30}$/'],
            'telegram' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_]{5,32}$/'],
            'instagram' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_.]{1,30}$/'],
            'show_email' => ['boolean'],
        ], [
            'phone.regex' => 'Enter a valid phone number, e.g. +993 61 234567.',
            'telegram.regex' => 'Telegram usernames are 5–32 letters, numbers or underscores.',
            'instagram.regex' => 'Instagram usernames use letters, numbers, dots or underscores.',
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $this->deleteAvatar($user);
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->boolean('remove_avatar')) {
            $this->deleteAvatar($user);
            $user->avatar = null;
        }

        $user->fill([
            'name' => $validated['name'],
            'bio' => $validated['bio'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'telegram' => $validated['telegram'] ?? null,
            'instagram' => $validated['instagram'] ?? null,
            'show_email' => $request->boolean('show_email'),
        ])->save();

        return redirect()->route('profile.edit')->with('success', 'Your profile has been updated.');
    }

    private function deleteAvatar(User $user): void
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
    }

    /**
     * @param  list<string>  $prefixes
     */
    private function normalizeHandle(?string $value, array $prefixes): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('#^https?://(www\.)?#i', '', $value);

        foreach ($prefixes as $prefix) {
            if (str_starts_with(strtolower($value), $prefix)) {
                $value = substr($value, strlen($prefix));
            }
        }

        return trim(ltrim($value, '@'), '/');
    }
}
