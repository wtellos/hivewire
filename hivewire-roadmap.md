# Hivewire — Phased Build Roadmap

A backend-first Laravel app: clients bring in projects, projects break into tasks, a team works them, and everything flows into a live activity feed (**The Buzz**).

**Stack:** Laravel + Livewire starter kit (auth included) · Pest · Pint · Larastan
**Focus:** backend (REST API, queues, performance, sensitive data, ORM, OOP, MVC, design patterns, polymorphic relations, policies, WebSockets)

---

## Phase 0 — Foundation

- `laravel new hivewire` with the Livewire starter kit
- Models + migrations + factories + seeders: `Client`, `Project`, `Task` (`make:model X -mfs`)
- Pivot tables (no `id()`, composite unique on the two FKs):
  - `project_members` (Project ↔ User)
  - `task_assignee` (Task ↔ User)
  - `task_follower` (Task ↔ User)

### Schema decisions locked in

| Table | Key decisions |
|---|---|
| `tasks` | `creator_id` → `users`, `project_id` (cascade on delete), `status` enum, `progress`, `start_date`, `end_date`, `description` (text) |
| `status` enum | `backlog`, `to_do`, `in_progress`, `in_review`, `done` (default `backlog`) |
| Pivots | `cascadeOnDelete()` on both FKs — deleting a task or user removes only the link row |

---

## Phase 1 — Core CRUD (MVC / ORM / OOP)

- Volt single-file components (the starter-kit convention: `resources/views/pages/**/⚡*.blade.php`) + Form Requests for Clients, Projects, Tasks
- Eloquent relationships on every model (with return types)
- Form Requests (`StoreClientRequest`, `StoreProjectRequest`, `StoreTaskRequest`, …); `progress` validated here: `integer|min:0|max:100`

### Current state

- `TaskController` + `StoreTaskRequest` / `UpdateTaskRequest` exist but nothing is wired — no routes, no views, no domain tests.
- No Client / Project UI exists at all.

### Design decisions locked in

| Decision | Choice | Why |
|---|---|---|
| UI pattern | Volt single-file components (`⚡index.blade.php` etc.), routes via `Route::livewire()` — the starter-kit convention (`settings/*` pages already work this way) | Reactive forms without writing JS |
| Validation | Form Requests own the rules (`rules()` + `authorize(): true` for now); Volt components validate against the request's rules | Validation lives in one place per US-1; Phase 3 swaps `authorize()` over to policies without touching rules |
| URLs | Nested where the domain is nested: `clients/{client}/projects`, `projects/{project}/tasks` | Reads like the data; route-model binding stays explicit |
| Existing `TaskController` | Kept as the task HTTP endpoint; Volt task components reuse its Form Requests | No throwaway work; going pure-Volt later is a clean delete |

### Mini-roadmap

Concrete steps, in order. `—` = no file touched.

| Step | Action | File |
|---|---|---|
| 1 | `php artisan make:request StoreClientRequest` (+ `UpdateClientRequest`) — `name` required, `email` nullable + valid | NEW: `app/Http/Requests/StoreClientRequest.php`, `UpdateClientRequest.php` |
| 2 | `php artisan make:request StoreProjectRequest` (+ `UpdateProjectRequest`) | NEW: `app/Http/Requests/StoreProjectRequest.php`, `UpdateProjectRequest.php` |
| 3 | Create the Volt client pages — index lists all clients, create form shows field errors, submit lands on detail with confirmation (US-1) | NEW: `resources/views/pages/clients/⚡index.blade.php`, `⚡create.blade.php`, `⚡show.blade.php` |
| 4 | In `⚡create`: validate against `StoreClientRequest` rules → `Client::create` → redirect `clients.show` + success flash | EDITED: `resources/views/pages/clients/⚡create.blade.php` |
| 5 | Create the Volt project pages (index / create / show / edit) | NEW: `resources/views/pages/projects/⚡*.blade.php` |
| 6 | Create the Volt task pages, reusing the existing `StoreTaskRequest` / `UpdateTaskRequest` rules (`progress` 0–100 lives there) | NEW: `resources/views/pages/tasks/⚡*.blade.php` |
| 7 | Register routes inside the `auth` group — `Route::livewire('clients', 'pages::clients.index')` … for all three entities | EDITED: `routes/web.php` |
| 8 | Add Clients / Projects / Tasks entries to the sidebar group | EDITED: `resources/views/layouts/app/sidebar.blade.php` |
| 9 | `php artisan make:test ClientTest --pest` — happy path + validation failure (US-1 DoD) | NEW: `tests/Feature/ClientTest.php` |
| 10 | `php artisan make:test ProjectTest --pest` — CRUD + validation per entity | NEW: `tests/Feature/ProjectTest.php` |
| 11 | `php artisan make:test TaskTest --pest` — CRUD + `progress` bounds through the existing requests | NEW: `tests/Feature/TaskTest.php` |
| 12 | `composer ci:check` | — |

### Local workflow

```powershell
.\vendor\bin\pint      # fix style
composer ci:check      # Pint (test mode) + Larastan + Pest, same as CI
```

---

## Phase 2 — Roles & Capabilities

- Every user carries a `role` — `admin`, `manager`, or `member` — stored as an enum-backed column on `users` and mirrored by a PHP enum.
- On top of roles sits a **`Capability` enum**: one case per actionable permission from the role matrix. Application code never checks raw roles; it checks capabilities (`$user->hasCapability(Capability::UpdateTask)`). Roles decide *which capabilities you get*; capabilities decide *what you may do*.
- `spatie/laravel-permission` stays deferred to Phase 10 (flagged, not committed) — build the enum version first so the learning reps aren't skipped.

### Why capabilities and not raw role checks?

- One source of truth for authorization — `Capability::grantedTo(UserRole $role)` — testable in a single place.
- The role matrix becomes data, not scattered `if ($user->role === 'admin')` conditionals across controllers and views.
- Phase 3 policies and Phase 7 API token scopes both consume capabilities — the same rule everywhere (web, API, tokens).

### Design decisions locked in

| Decision | Choice | Why |
|---|---|---|
| Storage | `role` enum column on `users`, default `member` | Simple, fast, no package; matches US-4 |
| PHP side | `App\Enums\UserRole` (string enum: `Admin`, `Manager`, `Member`) | Type-safe helpers reused in casts, factories, seeders |
| Permissions | `App\Enums\Capability`, one case per matrix row | Single permission vocabulary across the app |
| Granting | `Capability::grantedTo(UserRole $role): bool` | The matrix lives in exactly one place |
| Checking | `User::hasCapability(Capability $capability): bool` (+ `isAdmin()` / `isManager()` sugar) | Controllers and Form Requests never touch roles directly |
| Conditional rules (e.g. manager deletes client only if it has no projects) | Capability ✅ + model-level condition, enforced by Phase 3 policies | A capability says "may delete clients"; the policy adds "…and only childless ones" |
| Sensitive fields | `ViewClientSensitiveFields` capability gates email/phone | Phase 7 Sanctum token scopes inherit the same capability list |

### Capability matrix (target state)

| Capability | Admin | Manager | Member |
|---|---|---|---|
| `ManageUsersAndRoles` | ✅ | ❌ | ❌ |
| `ViewClientSensitiveFields` (email, phone) | ✅ | ✅ | ❌ |
| `CreateClient` / `UpdateClient` | ✅ | ✅ | ❌ |
| `DeleteClient` | ✅ | ⚠️ only if client has no projects | ❌ |
| `CreateProject` / `UpdateProject` / `DeleteProject` | ✅ | ✅ | ❌ |
| `ManageProjectMembership` (project ↔ user pivot) | ✅ | ✅ | ❌ |
| `ViewProjectAndTasks` | ✅ all | ✅ all | ✅ only own projects |
| `CreateTask` / `UpdateTask` | ✅ | ✅ | ✅ only own projects |
| `DeleteTask` | ✅ | ✅ | ✅ only own created tasks |
| `AssignTaskUsers` (assignee / follower pivots) | ✅ | ✅ | ❌ |

### Mini-roadmap

Concrete steps, in order. `—` = no file touched.

| Step | Action | File |
|---|---|---|
| 1 | `php artisan make:enum UserRole` | NEW: `app/Enums/UserRole.php` |
| 2 | Add cases `Admin`, `Manager`, `Member`; add `label(): string` helper | EDITED: `app/Enums/UserRole.php` |
| 3 | `php artisan make:migration add_role_to_users_table` | NEW: `database/migrations/…_add_role_to_users_table.php` |
| 4 | Add `role` enum (`admin` / `manager` / `member`), default `member` | EDITED: `…_add_role_to_users_table.php` |
| 5 | `php artisan migrate` | — |
| 6 | Add `role` cast, `isAdmin()`, `isManager()`, `hasRole()`, `hasCapability()` | EDITED: `app/Models/User.php` |
| 7 | `php artisan make:enum Capability` | NEW: `app/Enums/Capability.php` |
| 8 | Add one case per matrix row + `grantedTo(UserRole $role): bool` encoding the matrix above | EDITED: `app/Enums/Capability.php` |
| 9 | Set default `role` on the factory | EDITED: `database/factories/UserFactory.php` |
| 10 | `php artisan make:seeder DemoUserSeeder` (one user per role, documented passwords) | NEW: `database/seeders/DemoUserSeeder.php` |
| 11 | Register `DemoUserSeeder` in `DatabaseSeeder` | EDITED: `database/seeders/DatabaseSeeder.php` |
| 12 | `php artisan make:test UserRoleTest --pest --unit` — assert default role, helpers, cast round-trip | NEW: `tests/Unit/UserRoleTest.php` |
| 13 | `php artisan make:test CapabilityTest --pest --unit` — assert every capability × role matches the matrix | NEW: `tests/Unit/CapabilityTest.php` |
| 14 | `composer ci:check` (Pint + Larastan + Pest — same as CI) | — |

### Early enforcement (until Phase 3 policies exist)

- Gate Task store/update in the existing Form Requests with `$user->hasCapability(...)` so authorization has a real home from day one.
- Phase 3 replaces these checks with policies that consume the **same** capabilities — no vocabulary change, only the enforcement layer matures.
- Note: the existing `app/Policies/TaskPolicy.php` is an untouched artisan stub (all `return false`) — it gets its real body in Phase 3.

---

## Phase 3 — Policies & Sensitive Fields

- Policies per model (`ClientPolicy`, `ProjectPolicy`, `TaskPolicy`, `UserPolicy`), wired into Form Request `authorize()`
- Policies consume capabilities from Phase 2 **plus** model-level conditions (capability ✅ = "may delete clients", condition ⚠️ = "…and only when it has zero projects")
- Lock down sensitive client fields per role (also scopes what API tokens can see later)

## Phase 4 — The Buzz (Comments & Attachments)

- Polymorphic `Comment` and `Attachment` across Client / Project / Task
- Consider `Relation::requireMorphMap()` (enforced on read as of Laravel 13.34)

## Phase 5 — Service Layer

- Move logic like "reassign task → notify → log to the Buzz" out of controllers into Service classes
- Design patterns: Service / Repository, thin controllers

## Phase 6 — Queues (internal only)

- Task-assignment emails and weekly digest as Jobs
- Look at job duration on `JobProcessed`, `#[CountCrashesAsExceptions]`, `queue:flush --queue=`

## Phase 7 — Hivewire API + Make (both directions)

- `routes/api.php`, API Resources, Sanctum tokens scoped per client/team, versioned (`/api/v1/`), rate limiting
- Rebuild the Buzz feed to pull from the API
- **Outbound:** webhook jobs to Make on key events (task completed, client added, status changed)
- **Inbound:** endpoints Make (or any external caller) can use to create clients/tasks
- Later options: client portal, mobile app

## Phase 8 — Live Buzz (WebSockets)

- Laravel Reverb + Echo
- Broadcast new comments and status changes to the feed instantly

## Phase 9 — Monolith vs Microservices

- Discussion/diagram exercise: what would splitting the Buzz / notification / webhook piece into its own service look like?

## Phase 10 — Optional

- Filament panel for internal admin only (after the app is hand-built, so the learning reps aren't skipped)

---

## Pizza-shop cheat sheet

| Term | Pizza shop | Computer world |
|---|---|---|
| API | Menu | Interface for software to talk |
| Endpoint | Counter | Specific URL to send requests |
| REST | Sit-in rules | HTTP methods + JSON |
| Sanctum | Membership card | Token authentication |
| Postman | You pretending to be a customer | API testing tool |
| Make | Waiter delivering between shops | Integration tool |
| Controller | Kitchen | Handles the logic |
| JSON | Order ticket | Data returned |
| WebSocket | Kitchen bell | Server pushes updates instantly |
