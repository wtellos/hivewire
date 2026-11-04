# Hivewire — User Stories

Phase-based, following `hivewire-roadmap.md`.
Status: `backlog` · `ready` · `in progress` · `done`

---

## Phase 1 — Core CRUD (MVC / ORM / OOP)

### US-1 · Create a Client — `ready`

**As a** team manager
**I want to** register a new client (name, contact email, notes)
**So that** I can later attach their projects and tasks to one place

**Acceptance Criteria**

| # | Criteria |
|---|---|
| 1 | From a "Clients" page, I can open a form to add a client |
| 2 | Name is required; email must be a valid email or empty |
| 3 | On submit, the client is saved and I land on the client's detail page with a confirmation |
| 4 | I can see a list of all clients |
| 5 | Submitting an invalid form shows errors on the fields, nothing is saved |

**Definition of Done**
- Livewire component + route + view
- `StoreClientRequest` Form Request (validation lives there)
- Pest feature tests covering the happy path + validation failure

---

### US-2 · Create a Project under a Client — `backlog`

*(placeholder — to be written together before starting)*

### US-3 · Create a Task in a Project — `backlog`

*(placeholder — to be written together before starting)*

---

## Phase 2 — Roles & Policies

**Role matrix (target state)**

| Action | Admin | Manager | Member |
|---|---|---|---|
| Manage users & roles (US-5) | ✅ | ❌ | ❌ |
| View client sensitive fields — email, phone (US-6) | ✅ | ✅ | ❌ |
| Create / edit clients | ✅ | ✅ | ❌ |
| Delete client (orphans projects) | ✅ | ⚠️ only if no projects | ❌ |
| Create / edit / delete projects | ✅ | ✅ | ❌ |
| View a project & its tasks | ✅ | ✅ | only if project member |
| Create / edit tasks | ✅ | ✅ | only if project member |
| Assign / unassign task users | ✅ | ✅ | ❌ |
| Delete tasks | ✅ | ✅ | only own created tasks |

---

### US-4 · Give Users a Role — `ready`

**As a** developer
**I want** every user to carry a role — `admin`, `manager`, or `member` — stored as an enum column on `users`
**So that** every authorization decision in this phase has something concrete to check

**Assumption:** simple enum column, not the `spatie/laravel-permission` package (the package is a possible Phase 9 upgrade — flagged, not committed).

**Acceptance Criteria**

| # | Criteria |
|---|---|
| 1 | Migration adds `role` enum (`admin`, `manager`, `member`), default `member` |
| 2 | Seeder creates one demo user per role |
| 3 | `User` model exposes readable helpers (e.g. `isAdmin()`, `isManager()`) backed by a PHP enum |
| 4 | Login/auth behavior is unchanged — roles gate actions, not authentication |

**Definition of Done**
- Migration + PHP enum + model helpers
- Factory sets a default role; Pest tests assert the default and each helper

---

### US-5 · Only Admins Manage Roles — `backlog`

**As an** admin
**I want** to change any user's role, while nobody else can — and nobody can change their own
**So that** team structure stays controlled and nobody locks themselves out (or grants themselves more power)

**Acceptance Criteria**

| # | Criteria |
|---|---|
| 1 | Admin can update any user's role |
| 2 | Manager/member attempting a role change get 403 |
| 3 | Attempting to change your own role is rejected, even for admins |

**Definition of Done**
- `UserPolicy` with `updateRole()` (or `update()` with role-field check)
- Form Request authorization wired to the policy
- Pest tests: admin success, non-admin 403, self-change 403

---

### US-6 · Client Visibility and Sensitive Fields — `backlog`

**As a** manager
**I want** to see all client fields including email and phone
**So that** I can actually contact the people we work for

**As a** member
**I want** to see only client name and company
**So that** client contact details stay on a need-to-know basis

**Acceptance Criteria**

| # | Criteria |
|---|---|
| 1 | Admin & manager see `email` and `phone` on client index/detail |
| 2 | Member sees name and company only — no sensitive fields rendered or returned |
| 3 | Direct visits to URLs (guessing IDs) are also blocked from seeing sensitive data, not just hidden buttons |
| 4 | Fields chosen as "sensitive" are recorded in the story/roadmap so Phase 6 API tokens inherit the same rule |

**Definition of Done**
- `ClientPolicy` view rules enforced in controllers/components
- Blade/Livewire views conditionally render sensitive fields
- Pest tests per role asserting presence/absence of sensitive fields

---

### US-7 · Client Deletion Rules — `backlog`

**As an** admin
**I want** to delete any client — their projects become orphaned (`client_id` → NULL, per the Phase 0 FK decision)
**So that** I can clean up the client list without destroying work history

**As a** manager
**I want** to delete only clients that have no projects
**So that** I can't accidentally orphan a live project's billing/contact context

**Acceptance Criteria**

| # | Criteria |
|---|---|
| 1 | Admin can delete any client; its projects survive with `client_id = NULL` |
| 2 | Manager can delete a client only when it has zero projects; otherwise 403 |
| 3 | Member cannot delete clients |
| 4 | Deletion confirms the orphaning consequence in the UI before proceeding |

**Definition of Done**
- `ClientPolicy::delete()` encodes the role + projects-empty rule
- Pest tests: admin orphan path, manager blocked/with-projects, member 403

---

### US-8 · Project Policies — `backlog`

**As a** team member
**I want** to see and work on tasks only for projects I belong to
**So that** I don't see other teams' internal work

**Acceptance Criteria**

| # | Criteria |
|---|---|
| 1 | Member viewing a non-member project gets 403 (index filtered to own projects) |
| 2 | Admin & manager can view all projects |
| 3 | Only admin/manager can create, edit, or delete projects |
| 4 | Only admin/manager can change project membership (`members` pivot) |

**Definition of Done**
- `ProjectPolicy` (viewAny/view/update/delete/membership)
- Form Requests call `authorize()` against it
- Pest tests per role × action

---

### US-9 · Task Policies — `backlog`

**As a** task creator who is just a member
**I want** to edit or delete the tasks I created within my projects
**So that** I can fix my own mistakes without waiting for a manager

**Acceptance Criteria**

| # | Criteria |
|---|---|
| 1 | Member can view tasks of projects they belong to; others get 403 |
| 2 | Member can create/edit tasks only in their own projects |
| 3 | Member can delete only tasks they created; admin/manager can delete any |
| 4 | Only admin/manager can change `assignees`/`followers` (the pivots) |

**Definition of Done**
- `TaskPolicy` (view/create/update/delete/assign)
- Pest tests per role, including creator-vs-non-creator member distinction

---

### US-10 · Policies Enforced in Form Requests — `backlog`

**As a** developer
**I want** every Phase 1 Form Request to authorize through its model's policy
**So that** authorization lives in exactly one place and returns 403 consistently

**Acceptance Criteria**

| # | Criteria |
|---|---|
| 1 | All Store/Update Form Requests implement `authorize()` via the matching policy method |
| 2 | Unauthorized requests return 403 (not redirects, not silent failures) |
| 3 | No controller duplicates role checks that a policy already owns |

**Definition of Done**
- Refactor of Phase 1 Form Requests
- Pest tests hitting endpoints as each role

---

## Later phases

Stories for Phases 3–9 will be added as we reach each phase.
