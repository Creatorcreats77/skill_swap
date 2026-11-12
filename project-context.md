# SkillSwap — Project Context

SkillSwap is a Laravel web app where users trade skills: you list what you can **teach** ("offered") and what you want to **learn** ("wanted"), then search for partners whose skills are the mirror image of yours and send them a swap request.

## Tech stack

| Layer | Choice |
|---|---|
| Backend | PHP ^8.3, Laravel ^13.17 |
| Views | Blade (server-rendered, no SPA framework) |
| Styling | Tailwind CSS v4 via `@tailwindcss/vite`, custom utilities in `resources/css/app.css` |
| Build | Vite 8 + `laravel-vite-plugin` |
| Database | MySQL (`skill_swap` on 127.0.0.1:3306, per `.env`); SQLite file also present |
| Sessions | `database` driver |
| Tests | Pest 5 (`pestphp/pest-plugin-laravel`) |
| Lint/format | Laravel Pint |

## Running locally

```sh
composer setup        # install deps, copy .env, key:generate, migrate, npm install + build
composer dev          # runs `php artisan dev` (server + vite)
composer test         # clears config and runs the test suite
```

The app is served at `http://localhost:8000` (`APP_URL`).

## Domain model

```
users ──< user_skills >── skills
  │         (type: offered | wanted, proficiency: beginner | intermediate | expert)
  │
  ├──< swap_requests (sender_id, receiver_id, offered_skill_id, requested_skill_id, status)
  │         │
  │         └──< messages (swap_request_id, sender_id, body, is_read)
```

### Tables (`database/migrations/`)

- **users** — standard Laravel users plus nullable `bio` and `avatar`.
- **skills** — `name` (unique), `slug` (unique), `category` (nullable, e.g. "Programming").
- **user_skills** — pivot. `type` enum `offered|wanted`, `proficiency` enum (nullable). Unique on `(user_id, skill_id, type)`, so the same skill can be both offered and wanted by one user.
- **swap_requests** — `sender_id` / `receiver_id` → users; `offered_skill_id` (sender teaches) / `requested_skill_id` (sender learns) → skills; `status` enum `pending|accepted|declined|completed` (default `pending`); `initial_message` (nullable).
- **messages** — chat inside a swap request; `body`, `is_read` (default false).

All foreign keys cascade on delete.

### Models (`app/Models/`)

- `User` — `skills()`, `offeredSkills()`, `wantedSkills()` (belongsToMany filtered by `wherePivot('type', ...)`), `sentSwapRequests()`, `receivedSwapRequests()`.
- `Skill` — `users()`, `offeredBy()`, `wantedBy()`, `uniqueSlug()`.
- `SwapRequest` — `sender()`, `receiver()`, `offeredSkill()`, `requestedSkill()`, `messages()`.
- `Message` — `swapRequest()`, `sender()`.
- `User_Skill` — empty placeholder; the pivot is accessed through the relationships, not this model.

`offeredSkills()` / `wantedSkills()` use `withPivotValue('type', ...)`, so `attach`/`sync` on them write the correct `type` and only touch rows of that type.

## Routes (`routes/web.php`)

| Method | URI | Name | Controller | Middleware |
|---|---|---|---|---|
| GET | `/` | — | redirects to `matches.index` | — |
| GET/POST | `/register` | `register` | `AuthController@showRegister` / `register` | guest |
| GET/POST | `/login` | `login` | `AuthController@showLogin` / `login` | guest |
| POST | `/logout` | `logout` | `AuthController@logout` | auth |
| GET | `/profile/skills` | `profile.skills.index` | `UserSkillController@index` (overview) | auth |
| GET | `/profile/skills/edit` | `profile.skills.edit` | `UserSkillController@edit` (bulk editor) | auth |
| PUT | `/profile/skills` | `profile.skills.update` | `UserSkillController@update` | auth |
| GET | `/profile/skills/create` | `profile.skills.create` | `UserSkillController@create` | auth |
| POST | `/profile/skills` | `profile.skills.store` | `UserSkillController@store` | auth |
| GET | `/profile/skills/{skill}` | `profile.skills.show` | `UserSkillController@show` | auth |
| DELETE | `/profile/skills/{skill}` | `profile.skills.destroy` | `UserSkillController@destroy` (optional `type`) | auth |
| GET | `/matches` | `matches.index` | `MatchController@index` | auth |
| GET | `/swap-requests` | `swap.request.index` | `SwapRequestController@index` (inbox) | auth |
| POST | `/swap-requests` | `swap.request.store` | `SwapRequestController@store` | auth |
| GET | `/swap-requests/{swapRequest}` | `swap.request.show` | `SwapRequestController@show` (chat) | auth |
| PATCH | `/swap-requests/{swapRequest}` | `swap.request.update` | `SwapRequestController@update` (status) | auth |
| POST | `/swap-requests/{swapRequest}/messages` | `swap.request.messages.store` | `SwapRequestController@storeMessage` | auth |

## Key flows

- **Auth** (`AuthController`) — hand-rolled session auth (no Breeze/Jetstream). Register logs the user in and sends them to *My Skills*; login redirects to the intended URL or *Find Matches*.
- **Manage skills** (`UserSkillController`) — `index` shows what you teach/learn with Remove buttons; `edit` is a filterable checkbox grid (offer / want / level) that `update` syncs per type; `create` adds a new catalogue skill and attaches it as offered, wanted or both.
- **Swap requests** (`SwapRequestController`) — `store` checks the receiver really is a mirror match and blocks duplicate pending requests. The receiver accepts/declines, the sender can cancel (decline) while pending, and either side marks an accepted swap completed. Each request has a chat thread; only the two participants can see it.
- **Seeding** — `php artisan db:seed` runs `SkillSeeder` (idempotent starter catalogue) and creates `test@example.com` / `password`.
- **Matching** (`MatchController@index`) — the user picks one skill they want and one they can teach. A match is any *other* user who **offers** the wanted skill **and wants** the offered skill. Results render as cards with a "Request Skill Swap" button.

## Frontend

- Layout: `resources/views/layouts/app.blade.php` — sticky glass nav (Find Matches / My Skills / user + Logout), optional `@section('header')`, `@yield('content')`, footer. Animated blurred background blobs.
- Pages: `auth/login`, `auth/register`, `profile/skills/{index,edit,create,show}`, `matches/index`, `swap-requests/{index,show}`.
- Design language: green/emerald/teal glassmorphism. Custom Tailwind utilities in `app.css`: `glass`, `glass-soft`, `glass-input`, `hover-lift`, `btn-pop`, `stagger`, and animations `animate-blob`, `animate-fade-up`, `animate-slide-down`, `animate-pop-in`. Respects `prefers-reduced-motion`.

## Known gaps / TODO

1. **Profile fields unused** — `bio` and `avatar` are on `users` but there is no profile edit page.
2. **No real-time chat** — the swap request chat is a plain form post; new messages appear on reload.
