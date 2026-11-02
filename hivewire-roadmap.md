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
| Open decision | Client → Project: cascade or `restrictOnDelete()`? |

---

## Phase 1 — Core CRUD (MVC / ORM / OOP)

- Livewire/Blade + controllers for Clients, Projects, Tasks
- Eloquent relationships on every model (with return types)
- Form Requests (`StoreTaskRequest`, `UpdateTaskRequest`); `progress` validated here: `integer|min:0|max:100`

### Clean-up before pushing

- [ ] Raise CLI `memory_limit` in Herd's `php.ini` (PHPStan crashes at 128M)
- [ ] Add return types to `TaskController` methods (`View` / `RedirectResponse`)
- [ ] Add `: BelongsToMany` return types on `members()`, `assignees()`, `followers()`, `User::projects()`
- [ ] Check the odd `project()` method on the `Project` model
- [ ] Remove `->min(0)` from the `progress` column in the tasks migration (not a real Blueprint method)

### Local workflow

```powershell
.\vendor\bin\pint      # fix style
composer ci:check      # Pint (test mode) + Larastan + Pest, same as CI
```

---

## Phase 2 — Roles & Policies

- Admin / Manager / Member roles
- Policies per model, wired into Form Request `authorize()`
- Lock down sensitive client fields per role (also scopes what API tokens can see later)

## Phase 3 — The Buzz (Comments & Attachments)

- Polymorphic `Comment` and `Attachment` across Client / Project / Task
- Consider `Relation::requireMorphMap()` (enforced on read as of Laravel 13.34)

## Phase 4 — Service Layer

- Move logic like "reassign task → notify → log to the Buzz" out of controllers into Service classes
- Design patterns: Service / Repository, thin controllers

## Phase 5 — Queues (internal only)

- Task-assignment emails and weekly digest as Jobs
- Look at job duration on `JobProcessed`, `#[CountCrashesAsExceptions]`, `queue:flush --queue=`

## Phase 6 — Hivewire API + Make (both directions)

- `routes/api.php`, API Resources, Sanctum tokens scoped per client/team, versioned (`/api/v1/`), rate limiting
- Rebuild the Buzz feed to pull from the API
- **Outbound:** webhook jobs to Make on key events (task completed, client added, status changed)
- **Inbound:** endpoints Make (or any external caller) can use to create clients/tasks
- Later options: client portal, mobile app

## Phase 7 — Live Buzz (WebSockets)

- Laravel Reverb + Echo
- Broadcast new comments and status changes to the feed instantly

## Phase 8 — Monolith vs Microservices

- Discussion/diagram exercise: what would splitting the Buzz / notification / webhook piece into its own service look like?

## Phase 9 — Optional

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
