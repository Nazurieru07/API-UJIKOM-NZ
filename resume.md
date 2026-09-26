# Current Audit — 01.API-UJIKOM

Date: 2026-09-25
Scope: current working tree, unstaged diff, untracked Laravel API files, models, migrations, seeders, observers, state-changing controllers, notifications, console command, routes, tests, and read-only live DB checks.

No source code or database data changed during audit. Audit report written here only. No commit.

## Working tree

- Branch: `main`, ahead of `origin/main` by 3 commits.
- Before this report: 22 tracked files modified; untracked `AUDIT-2026-09-22.md`, `AUDIT-2026-09-23.md`, new API controllers, Form Requests, Resources, `HitungPeminjamanTelat`, and `PeminjamanTelatTest`.
- Untracked `*:Zone.Identifier` sidecar files exist under API/Request/Resource directories. They are filesystem metadata, not Laravel source; do not commit.
- Historical audit files are stale in places. Current tree already removed old GET mass-update from admin index, but API additions introduce separate unreviewed flows and queue worker is still absent.

## Confirmed issues

### C-01 — Critical — Fresh seed data makes every seeded tool unavailable

Location: `backend/database/seeders/AlatSeeder.php:14-51`; `backend/database/migrations/2026_09_19_153455_add_stok_kondisi_to_alat_table.php:15-18`; consumers `backend/app/Http/Controllers/PeminjamController.php:23-27` and `backend/app/Http/Controllers/PetugasController.php:140-158`.

Evidence: `AlatSeeder` sets `stok` and `status_kondisi`, but never sets `stok_baik`, `stok_rusak`, or `stok_rusak_parah`. Migration defaults all three new columns to `0`. Catalog and approval use `stok_baik`, not total `stok`.

Impact: fresh `migrate:fresh --seed` creates tools with `stok > 0` and `stok_baik = 0`; catalog hides them and approval rejects them. Current live DB is repaired, but fresh deployment/demo data is broken.

Fix: seed condition columns consistently (`stok_baik = stok`, other condition counts zero), or centralize invariant derivation and add a fresh-seed integration test.

### C-02 — High — Seeded returns contradict current workflow and enum vocabulary

Location: `backend/database/seeders/PeminjamanSeeder.php:12-42`; `backend/database/seeders/PengembalianSeeder.php:12-33`; `backend/database/migrations/2026_09_09_134207_add_status_request_to_pengembalian_table.php:12-25`; current validation `backend/app/Http/Controllers/PetugasController.php:313-317` and approval stock branches `backend/app/Http/Controllers/AdminController.php:704-727`.

Evidence: seeded return conditions are `Lengkap dan Berfungsi Baik` and `Lengkap, Casing Sedikit Tergores`, while current flow uses `Baik`, `Rusak Ringan`, `Rusak Berat`. Seeder omits `status_request`, so migration default is `menunggu`, even while seeded loans are marked `dikembalikan` or `telat`. Seeder denda also hardcodes `30000`, while current config is `3000` per day.

Impact: fresh seeded rows have pending returns attached to already-returned loans; invalid conditions bypass current branches and can restore total stock without restoring any condition bucket.

Fix: update seed values and statuses to current workflow, derive denda from config, and run seed + approval assertions in CI.

### C-03 — Critical — Admin status endpoint permits illegal transitions and double stock changes

Location: `backend/app/Http/Controllers/AdminController.php:1695-1773`.

Evidence: only `diajukan -> dipinjam` has stock logic, and only `-> dikembalikan` is rejected. Requests can directly change `dipinjam`/`telat` back to `diajukan`, change `dikembalikan` back to `dipinjam`, or repeat approval. The endpoint reads the loan without `lockForUpdate()` and reads tools without row locks.

Impact: direct request or concurrent admin requests can decrement stock twice, turn a returned loan active without withdrawing returned stock, then add stock again on delete/return. Status, stock, and audit trail diverge.

Fix: define explicit allowed transitions; reject all others; lock loan and all tool rows in one transaction; make approval an atomic conditional update. Reuse one approval service for web and API.

### C-04 — Critical — Web approval races on tool rows

Location: `backend/app/Http/Controllers/PetugasController.php:113-158`.

Evidence: loan row is locked at `:115-117`, but each tool is fetched with `Alat::findOrFail()` at `:137`; the code checks `stok`/`stok_baik` and then decrements at `:157-158` without locking the tool row.

Impact: two different loans can both observe one remaining good unit, both pass checks, and both decrement. Integer columns have no non-negative constraint, so `stok` or `stok_baik` can become negative. Existing double-approve test only covers the same loan, not competing loans.

Fix: lock each `Alat` with `lockForUpdate()` inside the transaction, in deterministic ID order; re-check both stock values after the lock. Add a concurrent approval test.

### C-05 — Critical — Admin return approval is not idempotent under concurrency

Location: `backend/app/Http/Controllers/AdminController.php:641-732`.

Evidence: transaction exists, but return row, loan row, and tool rows are loaded without `lockForUpdate()`. Two requests can both pass `status_request === 'menunggu'` at `:650-654`, both set `disetujui`, and both increment stock at `:704-728`.

Impact: one physical return can add stock twice and generate duplicate approval side effects/notifications.

Fix: lock return and loan rows, lock every tool row before checking/updating, then use a conditional status transition. Return success for an already-finalized identical request or reject it without changing stock.

### C-06 — Critical — API approval has same race plus wrong stock dimension

Location: `backend/app/Http/Controllers/API/PeminjamanController.php:148-168`.

Evidence: status check happens before transaction at `:150`; loan is never locked. Tool lock is present, but code checks/decrements only `stok` at `:163-167`, never `stok_baik`.

Impact: concurrent API approvals can both pass and reduce total stock twice. API can approve damaged-only stock and leave condition totals inconsistent. API and web approvals now implement different inventory rules.

Fix: lock loan before status check; lock tools; require/decrement `stok_baik` and total together; use one shared approval action.

### C-07 — Critical — API return endpoint restores stock before approval and leaves wrong final status

Location: `backend/app/Http/Controllers/API/PengembalianController.php:35-69`; request rules `backend/app/Http/Requests/Pengembalian/StorePengembalianRequest.php:17-25`.

Evidence: API creates a return with no `status_request`, so DB default is `menunggu` (`:50-56`), but immediately increments total stock at `:60-63`. It sets loan status to `dikembalikan` when on time and `telat` when late at `:45-58`; an overdue loan already marked `telat` is rejected at `:41`. Request accepts arbitrary condition text and client-supplied denda.

Impact: pending return can release inventory for re-loaning; overdue completion remains `telat` instead of `dikembalikan`; condition buckets never change; denda can be forged. API response claims completion while approval is still pending.

Related delete path `backend/app/Http/Controllers/API/PengembalianController.php:111-133` always decrements total stock and sets loan to `dipinjam`, regardless of `status_request`, and never adjusts condition buckets. Deleting a pending web/API return can therefore remove stock that was never restored.

Fix: make API use the same pending -> approved/rejected state machine as web. Validate enum values, compute denda server-side, mutate stock only once on approval, lock rows, and make delete behavior status-aware.

### C-08 — High — API inventory CRUD bypasses condition-stock invariant

Location: `backend/app/Http/Controllers/API/AlatController.php:27-37,53-69`; `backend/app/Http/Requests/Alat/StoreAlatRequest.php:15-32`; `backend/app/Http/Requests/Alat/UpdateAlatRequest.php:20-35`.

Evidence: API accepts free-form `status_kondisi`, passes it directly into `Alat::create/update`, and never derives `stok_baik`, `stok_rusak`, `stok_rusak_parah` from total stock. The web flow derives status and buckets, but API does not.

Impact: API-created tools get condition buckets at database defaults while total stock is positive; API edits can make status disagree with buckets. Catalog and approval then disagree with API inventory responses.

Fix: remove status input, derive condition buckets/status in one domain action, and validate all inventory mutations through that action.

### C-09 — High — API resource silently omits loan items

Location: `backend/app/Http/Resources/PeminjamanResource.php:23-31`; model relation `backend/app/Models/Peminjaman.php:31-34`; API eager loads `detailPinjams` at `backend/app/Http/Controllers/API/PeminjamanController.php:21,56,77`.

Evidence: resource checks `whenLoaded('detailPinjam')`, but model relation is `detailPinjams()`. Read-only live execution resolved a `PeminjamanResource` with keys `id,peminjam,tgl_pinjam,tgl_kembali_plan,status,info_pengembalian`; `item_dipinjam` was absent.

Impact: API clients cannot know which tools belong to a loan; reports and client-side reconciliation are incomplete.

Fix: use `detailPinjams` consistently and add an API resource test asserting item output.

### C-10 — High — API registration cannot satisfy database schema

Location: `backend/app/Http/Requests/Auth/RegisterRequest.php:14-19`; `backend/app/Http/Controllers/API/AuthController.php:17-29`; schema `backend/database/migrations/0001_01_01_000000_create_users_table.php:11-20`.

Evidence: request validates only email and password; controller never supplies `name`; database requires non-null `users.name` at `:13`. Email is not unique-validated and password has no minimum rule.

Impact: normal API registration reaches a database exception and returns 500; valid duplicate/weak credential input is not rejected at the boundary.

Fix: require `name`, unique email, and a password strength rule; return validation errors, not raw exception text.

### C-11 — High — API login has no validation or brute-force throttle

Location: `backend/app/Http/Requests/Auth/LoginRequest.php:23-27`; `backend/app/Http/Controllers/API/AuthController.php:47-64`; routes `backend/routes/api.php:13-15`.

Evidence: `LoginRequest::rules()` is empty. API login performs direct lookup/hash check with no `RateLimiter`; the web controller has a five-attempt limiter, but API login does not.

Impact: malformed requests reach authentication code and public API credentials can be brute-forced without the web limiter.

Fix: add email/password rules and a shared IP+identifier limiter to both login paths.

### C-12 — Critical — Destructive cascade endpoints erase transaction detail without stock reconciliation

Locations: web `backend/app/Http/Controllers/AdminController.php:466-485,1279-1324,1455-1466`; API `backend/app/Http/Controllers/API/AlatController.php:77-89`, `backend/app/Http/Controllers/API/UserController.php:74-84`, `backend/app/Http/Controllers/API/KategoriController.php:64-69`; FKs `backend/database/migrations/2026_07_22_065156_03_create_alat_table.php:14-22`, `2026_07_22_070104_04_create_peminjaman_table.php:14-20`, `2026_07_22_070345_05_create_detail_pinjam_table.php:14-19`, `2026_07_22_070659_07_create_log_aktivitas_table.php:11-18`.

Evidence: deleting a tool cascades `detail_pinjam`; deleting a category cascades tools and then details; deleting a user cascades loans, returns/logs through related FKs. Web user deletion blocks only active loans, not historical loans. API user/tool/category deletion has no reference or active-loan guard.

Impact: active loan detail can disappear while loan remains; stock cannot be restored on return. Historical loans and audit logs can be silently erased. Current DB has no orphaned loans, but endpoints permit creating them.

Fix: use restrictive FKs for master/history data, reject deletion when referenced, or implement a locked archival flow that reconciles active stock before deletion. Apply same guard to web and API.

### C-13 — High — Queue and scheduler are configured but never run in Docker

Locations: `docker-compose.yml:18-30`; `backend/routes/console.php:20-22`; actual `backend/.env:38` (`QUEUE_CONNECTION=database`).

Evidence: compose starts only `php artisan serve`; no `queue:work` and no `schedule:work`. `docker top laravel-api` showed only the web server. `schedule:list` shows hourly `app:hitung-peminjaman-telat`, but no scheduler process exists. Read-only DB query found `jobs=8`, `notifications=73`, queue default `database`.

Impact: queued database notifications do not reach users; overdue loans are never marked by scheduler. Tests use `QUEUE_CONNECTION=sync` (`backend/phpunit.xml:25-31`), so this production failure is invisible to the suite.

Fix: run queue worker and scheduler under Supervisor, separate compose services, or an equivalent process manager; add deployment health checks and queue integration tests.

### C-14 — Medium — Scheduled overdue updates skip audit logs in production

Location: `backend/app/Console/Commands/HitungPeminjamanTelat.php:30-46`; `backend/app/Observers/PeminjamanObserver.php` auth guards at `:15-25,42-46`; test workaround `backend/tests/Feature/PeminjamanTelatTest.php:59-68`.

Evidence: command correctly saves each instance, but observer returns when `Auth::check()` is false. Real scheduler has no logged-in session. Test calls `actingAs()` before invoking Artisan, which does not represent production scheduler execution.

Impact: overdue status changes happen without `log_aktivitas`, weakening audit completeness.

Fix: support a system actor (`user_id` nullable plus actor type, or dedicated system user) and test the command without a fake HTTP session.

### C-15 — Medium — Condition status is not recomputed after several stock mutations

Locations: web approval `backend/app/Http/Controllers/PetugasController.php:148-158`; return approval `backend/app/Http/Controllers/AdminController.php:704-732`; delete/reversal `AdminController.php:999-1041`; API return `backend/app/Http/Controllers/API/PengembalianController.php:60-63`.

Evidence: these paths change condition buckets or total stock but never recompute `status_kondisi`. Only `updateAlat` and `ubahKondisiAlat` explicitly call `Alat::kondisiMayoritas()`.

Impact: a tool can display `Baik` while damaged stock is the majority, or retain an old condition after return/repair. Current live check found all 6 tools consistent, so this is a reachable code defect, not current-row corruption.

Fix: centralize every stock transition and recompute status in the same locked transaction; add invariant assertions after approve/return/delete/repair.

### C-16 — Medium — Migration rollback paths are incomplete or unsafe

Locations: `backend/database/migrations/2026_07_22_065156_03_create_alat_table.php:26-32`, `2026_07_22_070104_04_create_peminjaman_table.php:24-30`, `2026_07_22_070345_05_create_detail_pinjam_table.php:23-29`, `2026_07_22_070631_06_create_pengembalian_table.php:26-32`; nullable rollback `backend/database/migrations/2026_09_21_150927_make_petugas_id_nullable_in_pengembalian_table.php:18-25`.

Evidence: four `down()` methods are no-ops. Nullable rollback attempts to make `petugas_id` non-null even though current data includes an admin-handled return with `petugas_id = NULL`.

Impact: rollback cannot restore schema and can fail or leave partial state during deployment recovery.

Fix: implement real reverse operations and make rollback preconditions explicit; never change nullable to non-null without handling existing null rows.

## Risks and coverage gaps

- Pending web loans do not reserve stock. `backend/app/Http/Controllers/PeminjamController.php:104-129` and `AdminController.php:1619-1662` only check current stock; approval checks later. Many pending requests can promise the same units and later fail approval. Either reserve atomically or state that approval is first-come-first-served.
- `detail_pinjam` has no unique `(peminjaman_id, alat_id)` constraint (`backend/database/migrations/2026_07_22_070345_05_create_detail_pinjam_table.php:14-19`). Web admin and API request rules do not use `distinct` (`AdminController.php:1606-1616`; `StorePeminjamanRequest.php:20-24`). Current live duplicate query returned zero rows, but crafted/API requests can create duplicate detail rows.
- `perbaikiAlat` and `ubahKondisiAlat` check a tool loaded before transaction and do not lock it (`AdminController.php:318-358,396-426`). Concurrent repairs/moves can overdraw condition buckets. Add row locks and a concurrency test.
- `AdminController::ajukanPengembalianAdmin` stores `petugas_id = auth()->id()` at `:878-885`, while model/view semantics treat NULL as admin-handled (`Pengembalian.php:39-47`; report view `resources/views/petugas/laporan/index.blade.php:313-320`). New admin-created returns will be labeled by admin name and may notify admin as if admin were petugas. Decide one representation.
- `AdminController::storePeminjaman` validates only `exists:users,id` (`:1596-1604`), not `role=peminjam`; UI search filters role, but direct requests can create loans owned by admin/petugas.
- API list endpoints use unbounded `get()` (`API/PeminjamanController.php:18-25`, `API/PengembalianController.php:18-32`, `API/AlatController.php:16-24`, `API/UserController.php:17-23`, `API/KategoriController.php:18-25`, `API/LogAktivitasController.php:12-20`). This becomes a memory/latency failure as data grows; API report is paginated, web lists are mostly paginated.
- API route roles contradict controller intent: `routes/api.php:23-29` puts loan creation and history under `petugas,admin`; `:31-37` puts loan listing under `admin,petugas`; detail/update/delete are admin-only at `:40-50`, although controller contains peminjam ownership checks (`API/PeminjamanController.php:68-92,125-136`). Peminjam cannot use its own API create/list/edit/cancel flow; petugas can create a loan whose `user_id` is the petugas. Align route roles with business ownership.
- API `AlatResource.php:18` prepends `storage/` to API-managed paths, while web AdminController stores `storage/alat/...` at `:182-190`; web-created image paths can become `storage/storage/...` in API output.

## Verified strengths

- Docker test run: **25 tests passed, 76 assertions**. Includes web role checks, login throttle, condition moves, status-majority calculation, report filtering, and overdue command.
- PHP lint passed for all current PHP files under `app`, `database`, and `routes` in the running container.
- `php artisan route:list` loaded 88 routes; protected web/API groups have auth and role middleware. `schedule:list` registered the overdue command hourly.
- All migrations currently report `Ran`; unique `pengembalian.peminjaman_id` migration is applied.
- Read-only live DB checks: 6 tools have condition sum equal to total and majority status; 15 loans and 15 returns; zero duplicate return keys; zero loans without details; zero invalid current return conditions/statuses; zero approved-return/loan-status mismatches.
- Web borrower submission uses a transaction and `Alat::lockForUpdate()` before rechecking `stok_baik` (`PeminjamController.php:92-129`). Web petugas approval has a loan status guard and loan row lock (`PetugasController.php:113-129`). These are good foundations, but tool-row locks remain required.
- All six notification classes implement `ShouldQueue`. This is correct code intent; runtime worker absence remains C-13.
- Models use explicit `$fillable`, typed relations, and integer/date casts. Most web read queries eager-load relations; web return/pending lists now paginate.

## Recommended order

1. Unify web/API loan and return state transitions in one service/action. Add row locks, conditional status updates, condition-stock updates, and invariant checks.
2. Fix seeders and add fresh-migration/seed workflow tests.
3. Block destructive master/user deletion or replace cascades with archival/restrict behavior.
4. Correct API route roles, request validation, resource relation, registration, and API throttle.
5. Add queue worker and scheduler processes to deployment.
6. Add concurrent approval/return tests, API flow tests, seed tests, and observer tests without `actingAs()` for scheduler execution.
