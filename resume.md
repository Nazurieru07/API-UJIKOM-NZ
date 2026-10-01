# Audit 01.API-UJIKOM — 2026-09-26 (re-audit, working tree @ d6d0290)

Laravel 12.64.0 + Sanctum 4, PHP 8.4.25, Docker (laravel-api, MySQL-API, phpMyadmin-API).
Test suite: **32 passed / 92 assertions**. Branch `main`, ahead of origin 4.
Working tree: 25 modified + 12 untracked files (the whole API/Requests/Resources layer).

## Incorrect findings from the 2026-09-25 report

Several prior findings were carried over from an older tree and are FALSE for the
current revision. Listed so they are not re-litigated:

| Prior claim | Reality in this tree |
|---|---|
| Web approval reads tool rows without lock | `PetugasController.php:139` reads tools inside the transaction, loan locked at `:122-124`. A narrower real defect remains — see **N-13**. |
| Admin return approval not idempotent | `setujuiPengembalian:650` guards `status_request !== 'menunggu'` inside the transaction at `:641`. Not reproducible as described. |
| API has no auth | All 35 `api/*` routes carry `auth:sanctum` + role middleware. |
| destroyUser cascades without check | `destroyUser:1283-1303` blocks self-delete and active loans. |
| Some forms missing @csrf | All 24 views with POST/PUT/DELETE forms have a matching `@csrf`. |
| `{!! !!}` XSS present | Zero occurrences across all 34 views. |

The web layer in this revision is materially better than the API layer. The real
defect class is the **web/API divergence**, not web breakage.

## Confirmed issues

### N-01 — Critical — `AjukanEditPeminjamanRequest.php` does not parse

`backend/app/Http/Requests/AjukanEditPeminjamanRequest.php:13-14`

```php
         = ->route('peminjaman');
        return  && ->user_id === auth()->id();
```

Every `$` stripped. `php -l` → `Parse error: syntax error, unexpected token "=" ... on line 13`.
The edit feature passes its 7 tests only because `PeminjamController::ajukanEditPeminjaman`
duplicates the rules inline (`:213-222`). Dead weight that fatals the moment anything
type-hints it.

**Fix:** restore `$peminjaman = $this->route('peminjaman'); return $peminjaman && $peminjaman->user_id === auth()->id();`
then type-hint the FormRequest, or delete the file. Add a `php -l` sweep over `app/` to CI.

### N-02 — Critical — API approval decrements only `stok`, never `stok_baik`

`backend/app/Http/Controllers/API/PeminjamanController.php:160-168`

```php
$alat->decrement('stok', $detail->jumlah);   // stok_baik never touched
```

Web (`PetugasController.php:161-162`) decrements **both**. Reproduced on sqlite: API-approve
a 3-unit loan on `stok=10/stok_baik=10` → `stok=7`, `stok_baik=10`. Invariant
`stok == stok_baik + stok_rusak + stok_rusak_parah` breaks, and `Alat::scopeTersedia`
(`Alat.php:74-77`) keeps advertising physically-gone units. Guard at `:163` tests `stok`
only, so damaged-only stock is loanable via API.

**Fix:** decrement both after `lockForUpdate()` re-check, via one shared approval action.

### N-03 — Critical — API return releases stock before approval

`backend/app/Http/Controllers/API/PengembalianController.php:35-69`

No `status_request` state machine. `store()` creates the return at `:41-47` (default
`menunggu`), then immediately mutates loan status at `:49` and restores stock at `:51-53`.
Reproduced: 5 units out → API submit gives `stok=5` while return is still `menunggu`;
web approval then credits again → `stok=10` against 5 physical units. `destroy():114-133`
decrements unconditionally, so deleting a pending return removes stock never added
(reproduced `3 → -2`).

**Fix:** API store creates a pending request only; validate `kondisi_kembali` against the
enum; drop client-supplied `denda`; make `destroy()` reverse stock only when
`status_request === 'disetujui'` (mirror `AdminController::destroyPengembalian`).

### N-04 — Critical — Seeders contradict the current workflow

`backend/database/seeders/AlatSeeder.php:13-57`, `PengembalianSeeder.php:12-33`

`AlatSeeder` writes only `stok` + `status_kondisi`; the three condition columns default
to 0 (`2026_09_19_153455:55-58`). A fresh `migrate:fresh --seed` creates 5 tools
simultaneously **invisible in the catalog** (`scopeTersedia` = `stok_baik > 0`) AND
**unloanable** at approval. `PengembalianSeeder` seeds conditions the approval branches
cannot match (`'Lengkap dan Berfungsi Baik'`), omits `status_request` (returns sit at
`menunggu` against already-`dikembalikan` loans), hardcodes `denda=30000` vs
`config('denda.keterlambatan_per_hari')=3000`.

**Fix:** seed `stok_baik = stok` (others 0), use `Baik`/`Rusak Ringan`/`Rusak Berat`, set
`status_request='disetujui'` for settled returns, compute denda from config. Add a
seeder-consistency test.

### N-05 — Critical — API Alat CRUD bypasses the condition invariant

`backend/app/Http/Controllers/API/AlatController.php:27-43,53-69`

`StoreAlatRequest` requires free-form `status_kondisi` and never derives buckets. An
API-created tool lands with `stok=10, stok_baik=0` — invisible and unloanable, the defect
the web path avoids at `AdminController.php:168-176`. `update()` identical.

**Fix:** drop `status_kondisi` from API input; derive buckets in one place
(`stok_baik = stok` on create, `stok - (stok_rusak + stok_rusak_parah)` on update) shared
by web and API.

### N-06 — Critical — Destructive cascades erase history and active loan detail

`backend/app/Http/Controllers/API/UserController.php:74-85`, `API/AlatController.php:77-90`,
`API/KategoriController.php:64-70`

All master/history FKs are `cascadeOnDelete` (`2026_07_22_070104:16`,
`2026_07_22_070345:16-17`, `2026_07_22_070631:16,20`, `2026_07_22_070659:15`). The API
destroy endpoints have **no guard** — an admin token can DELETE a user with an ACTIVE
loan: loan and details cascade away while the stock those tools occupied is never
restored. `PeminjamanObserver::deleted` returns early on `!Auth::check()`, and the API
guard is sanctum, not session. Web `destroyUser:1283-1303` blocks active loans; the API
does not.

**Fix:** port the web guard to the API controllers (409 on active loans, block
self-delete). Switch `peminjaman.user_id` and `log_aktivitas` to `restrictOnDelete`.

### N-07 — High — API login has no throttle, `LoginRequest` has zero rules

`backend/app/Http/Requests/Auth/LoginRequest.php:23-28` — `rules()` returns `[]`.
`bootstrap/app.php` never calls `$middleware->throttleApi()`, so `POST /api/login` carries
no rate limit while web login is throttled 5/60s (`AuthController.php:31-39`,
`LoginThrottleTest`).

**Fix:** add email/password rules; add `$middleware->throttleApi()`; port the same
`email|ip` RateLimiter key into `API\AuthController::login`.

### N-08 — High — API registration cannot satisfy the schema and leaks errors

`backend/app/Http/Requests/Auth/RegisterRequest.php:14-20`

Rules validate email + password only, but `API\AuthController::register` writes `name`
(NOT NULL, no default) and `no_hp`/`alamat` — all unvalidated. Registration without `name`
passes validation, throws a DB constraint, and the catch at `AuthController.php:39-44`
returns HTTP 500 with `$e->getMessage()` exposed — raw DB error text to an anonymous
caller. No unique-email rule, no password minimum.

**Fix:** add `name`, `Rule::unique('users','email')`, `Password::min(8)`; return 422
instead of catching into a 500.

### N-09 — High — API route roles contradict controller ownership logic

`backend/routes/api.php:23-57`

`POST /api/peminjaman` is `role:petugas,admin` (`:26`) but `store():39` hardcodes
`user_id => $user->id` — a petugas calling it creates a loan owned by the petugas.
GET/PUT/DELETE `/api/peminjaman/{id}` are admin-only (`:45-46`) while show/update/destroy
each carry `role === 'peminjam'` 403 guards (`:71-73,84-86,128-130`) that can never fire.
Peminjam has no usable API path despite being the role registration creates.
`GET /api/katalog` is registered twice (`:43` and `:56`).

**Fix:** put create/list/own-loan endpoints under `peminjam,admin`; move approve under
`petugas,admin`; drop dead peminjam branches or widen the groups; remove the duplicate.

### N-10 — High — `PeminjamanResource` checks a relation that does not exist

`backend/app/Http/Resources/PeminjamanResource.php:23-31`

Uses `whenLoaded('detailPinjam')` and `$this->detailPinjam->map(...)`, but the model
defines `detailPinjams()` (`Peminjaman.php:35-38`) and every API caller eager-loads
`detailPinjams.alat`. Runtime probe: `item_dipinjam` absent from every API loan response.

**Fix:** `whenLoaded('detailPinjams', fn() => $this->detailPinjams->map(...))`. Add a
resource test asserting `item_dipinjam` is populated.

### N-11 — High — Admin status endpoint permits illegal transitions

`backend/app/Http/Controllers/AdminController.php:1695-1793`

`updateStatusPeminjaman` validates the full enum but only guards `->dikembalikan`.
Reproduced on sqlite: `dikembalikan → dipinjam` accepted, re-activating a loan whose
return is already `disetujui` and stock already credited; a subsequent
`diajukan → dipinjam` decremented again (`3 → 1`). Loan read at `:1699` with no lock,
tools at `:1731` with no lock.

**Fix:** explicit allowed-transition map, reject everything else; lock loan and tool rows
inside the transaction; route `diajukan→dipinjam` through the same shared approval action.

### N-12 — High — Queue worker and scheduler absent from Docker

`docker-compose.yml:18-30`, `.env:38`

`app-laravel` runs only `php artisan serve`. `docker top laravel-api` shows no
`queue:work` and no `schedule:work`, yet `QUEUE_CONNECTION=database` and all 8
notifications implement `ShouldQueue`. Live DB held `jobs=16`, `notifications=73` at
audit time. `schedule:list` registers `app:hitung-peminjaman-telat` hourly with no process
to run it. `phpunit.xml:30` forces `QUEUE_CONNECTION=sync`, so the suite cannot detect
this.

**Fix:** add a `queue-worker` compose service (`queue:work --tries=3 --backoff`) plus
`schedule:work`, with a healthcheck. Add one queue integration test outside the sync
driver.

### N-13 — High — No lock on tool rows inside web approval transaction

`backend/app/Http/Controllers/PetugasController.php:113-158`

The loan row is locked (`:122-124`), but `Alat::findOrFail($detail->alat_id)` at `:139`
reads tools unlocked. Two loans competing for the last good unit both pass the `stok_baik`
check and both decrement → `stok_baik = -1`.

**Fix:** `Alat::lockForUpdate()->findOrFail($detail->alat_id)` in deterministic `alat_id`
order, re-check both `stok` and `stok_baik` after the lock. Add a concurrency test with
two different loans against the same unit.

### N-14 — High — Unbounded API list endpoints

`API/PeminjamanController.php:25,185`, `PengembalianController.php:28`,
`AlatController.php:19,94`, `UserController.php:19`, `KategoriController.php:20`,
`LogAktivitasController.php:14`

Only `LaporanController::index` paginates. Every other API list does unbounded `->get()`
with 2-3 relations eager-loaded per row. All web lists paginate.

**Fix:** paginate every index, reusing `LaporanController`'s validated `per_page` rule
(min 1, max 100).

### N-15 — Medium — `status_kondisi` not recomputed after stock mutations

`Alat::kondisiMayoritas()` called only from `AdminController.php:172,275,431`. Web approval
(`PetugasController.php:161-162`), return approval (`AdminController.php:709-727`), return
deletion (`:1009-1034`), the edit flow (`PetugasController.php:577-598`) and all API stock
moves mutate buckets without recomputing `status_kondisi`.

**Fix:** recompute inside the same locked transaction as every stock transition, ideally
by centralising the transition in one action.

### N-16 — Medium — Admin-created returns mis-attribute the officer

`backend/app/Http/Controllers/AdminController.php:878-886`

`ajukanPengembalianAdmin` stores `petugas_id = auth()->id()`, which for an admin is the
admin id. Model/view semantics treat NULL as admin-handled (`Pengembalian.php:39-47`;
laporan shows "Admin"). The create branch labels the return with the admin name and
`setujuiPengembalian:738-742` notifies the admin as if they were the petugas. The update
branch (`:869-875`) does not set `petugas_id` at all.

**Fix:** leave `petugas_id` NULL for admin-handled returns, in both branches.

### N-17 — Medium — Admin can create loans for non-borrower roles

`backend/app/Http/Controllers/AdminController.php:1594-1617`

`storePeminjaman` validates only `exists:users,id` — the UI filters `role='peminjam'`
(`:1573-1576`, `searchUser:1852-1855`) but a direct POST can create a loan owned by an
admin or petugas. `alat_id.*` has no `distinct`; `detail_pinjam` has no unique
`(peminjaman_id, alat_id)`.

**Fix:** `Rule::exists('users','id')->where('role','peminjam')`, add `distinct`, add the
unique index.

### N-18 — Medium — New edit-peminjaman flow has concurrency holes

`backend/app/Http/Controllers/PetugasController.php:556-646`

`setujuiEditPeminjaman` locks the request row first, then `DB::beginTransaction()` at
`:566` — the lock is acquired outside the transaction so it does not participate.
`tolakEditPeminjaman:627-645` has no lock or transaction at all. Duplicate-request
protection exists only in app code (`PeminjamController.php:203-211`), not as a DB
constraint. `aksi='hapus'` is not checked against the loan contents; `tambah` for an
alat already in the loan creates a duplicate detail row. Validation mixes positional
arrays — `$validated['jumlah'][$i]` breaks if `alat_id`/`jumlah`/`aksi` differ in length.

**Fix:** open the transaction before any lock; lock loan + tool rows in deterministic
order; add a partial unique index on `(peminjaman_id) WHERE status='menunggu'`; validate
each detail row as a struct, not three parallel arrays.

### N-19 — Medium — Scheduled overdue updates skip audit logs in production

`backend/app/Console/Commands/HitungPeminjamanTelat.php:30-46`

The command iterates per instance (correct — observers fire), but
`PeminjamanObserver::updated` returns early unless `Auth::check()`. The real scheduler has
no session, so `dipinjam → telat` is never logged. `PeminjamanTelatTest` uses `actingAs()`
before invoking Artisan, which does not reproduce scheduler execution.

**Fix:** support a system actor (nullable `user_id` + actor type, or a dedicated system
user) and test the command without a fake session.

### N-20 — Medium — `AlatResource` / `UserResource` double-prefix image paths

`backend/app/Http/Resources/AlatResource.php:18`, `UserResource.php:19`

Both build `url('storage/' . $this->gambar)`, but web controllers store paths that already
include the prefix (`AdminController.php:189,302`: `'storage/alat/' . $filename`).
Web-created records render as `.../storage/storage/alat/...`. The API controller stores
bare `alat/xxx.jpg` (`AlatController.php:115`), so the two write paths are inconsistent
by construction.

**Fix:** normalise on one representation — store the bare path and prefix only in the
resource, with a migration to strip the prefix from existing rows.

### N-21 — Medium — `SESSION_ENCRYPT=false` with a shared DB session store

`backend/.env:32`

`SESSION_DRIVER=database`, `SESSION_ENCRYPT=false`. The sessions table holds the payload
in plaintext, and the compose file publishes MySQL on host port 3306 with
`api_ujikom/api_ujikom` and root `passwordroot` in cleartext (`docker-compose.yml:8-12`).

**Fix:** `SESSION_ENCRYPT=true` in `.env` and `.env.example`. Do not publish MySQL on a
host port in a shared environment; source compose passwords from a `.env` file.

### N-22 — Medium — Migration `down()` methods are no-ops

`2026_07_22_065156_03:26-32`, `2026_07_22_070104_04:24-30`,
`2026_07_22_070345_05:23-29`, `2026_07_22_070631_06:26-32`

Four `down()` bodies are empty, so rollback cannot restore schema. Separately,
`2026_09_21_150927`'s rollback tries to make `petugas_id` non-null while live data
contains admin-handled returns with `petugas_id = NULL` — it would fail.

**Fix:** implement real reverse operations; never change nullable → non-null without
handling existing NULL rows.

### N-23 — Medium — God-object `AdminController` (~1926 lines, ~8 responsibilities)

`backend/app/Http/Controllers/AdminController.php:1-1926`

Dashboard, alat CRUD, condition repair/move, pengembalian approval, user CRUD, kategori
CRUD, peminjaman CRUD, live search and log viewing in one class. Condition-bucket math at
`:704-728`, denda computation at `:664-685` and the loan state machine at `:1721-1773`
live inline in HTTP handlers. This structure is the direct cause of the web/API
divergence — each transport hand-rolls its own copy because no shared service exists.

**Fix:** split by resource and move every stock/state transition into single-purpose
actions (`ApprovePeminjaman`, `ApprovePengembalian`, `RepairAlat`) that both transports
call.

## Risks and coverage gaps

- **Pending loans do not reserve stock.** `PeminjamController.php:104-120` and
  `AdminController.php:1632-1662` only check current stock; approval checks later. Many
  pending requests can promise the same units and later fail approval.
- **No API tests at all.** 32 tests exist; every one exercises the web layer or pure
  model logic. The entire `app/Http/Controllers/API` tree (9 controllers, 35 routes) has
  zero coverage — which is why N-02, N-03, N-10, N-14, N-06 and N-08 shipped undetected.
- **No concurrency tests.** N-13 and the admin-transition race are only provable with
  interleaved requests; the suite has no such harness.
- **No seeder tests.** N-04 is invisible to CI because nothing asserts seed output.
- **`perbaikiAlat` / `ubahKondisiAlat` read the tool before the transaction**
  (`AdminController.php:318-358, 396-426`). Concurrent repairs/moves can overdraw
  condition buckets.

## Verified strengths

- **32 tests / 92 assertions passing** — role denial, login throttle, condition
  arithmetic, the return report, the full edit-peminjaman approval flow including the
  duplicate-request guard, and the overdue command. Real coverage, not smoke tests.
- **Web borrower submission is concurrency-safe**: `PeminjamController.php:85-120` opens
  the transaction, `Alat::lockForUpdate()` at `:101`, re-checks `stok_baik` at `:107`.
- **Web petugas approval guards state correctly**: `setujuiPeminjaman` locks the loan row
  (`:122-124`), rejects non-`diajukan` (`:127`), validates both total and condition stock
  (`:142-153`) before decrementing.
- **Admin return approval computes the fine server-side**:
  `setujuiPengembalian:664-690` derives overdue days from the dates and multiplies
  `config('denda.keterlambatan_per_hari')`; damage fine and lateness fine live in
  separate columns so neither overwrites the other.
- **Condition-bucket arithmetic on return is correct and documented**:
  `setujuiPengembalian:704-728` always increments total stock and routes each unit to the
  matching bucket; `destroyPengembalian:986-1055` mirrors it in reverse with per-bucket
  sufficiency checks.
- **Admin status endpoint blocks the return bypass**: `updateStatusPeminjaman` refuses
  `dikembalikan` outside approval (`:1760-1767`).
- **No SQL injection surface**: the only raw SQL is static `orderByRaw CASE` expressions
  (`AdminController.php:1110`, `PetugasController.php:72`) with hardcoded literals.
- **No XSS via `{!! !!}`**: zero occurrences across all 34 views. The dynamic JS in
  `admin/peminjaman/create.blade.php` interpolates into `innerHTML` but only from
  admin-controlled master data; `katalog.blade.php:1222-1230` uses `textContent`.
- **CSRF discipline complete**: every one of the 24 views containing a POST/PUT/DELETE
  form has a matching `@csrf`.
- **Observer-based audit trail is sound design**: three observers registered in
  `AppServiceProvider:455-457`, with a deliberate exception for seeder/artisan runs.
  `HitungPeminjamanTelat` iterates per instance specifically so the observer still
  fires, and `PeminjamanTelatTest` asserts it.
- **Dangerous web deletions guarded**: `destroyUser` blocks self-deletion and active
  loans; `destroyKategori` checks tool references; `tolakPeminjaman` only acts on
  `status='diajukan'` where no stock has moved.
- **Validation thorough where it counts**: `StorePeminjamanRequest` uses
  `after_or_equal:today` and per-item `Rule::exists`; `StoreUserRequest`/
  `UpdateUserRequest` use `Password::min(8)->letters()->numbers()` with a
  correctly-scoped `Rule::unique(...)->ignore()`; uploads are
  `image|mimes:jpeg,png,jpg|max:2048` everywhere.
- **Schema integrity enforced in the DB**: `pengembalian.peminjaman_id` is UNIQUE
  (`2026_09_22_090000`), `role` is a 3-value enum with a safe default, condition columns
  are NOT NULL integers with defaults.
- **Models disciplined**: explicit `$fillable` everywhere, `$hidden` on User, typed
  relationships, integer/date casts on all numeric and date columns (`Alat.php:36-44`
  integer-casts all four stock buckets, preventing decimal drift from string form input).
- **Secrets hygiene**: `.env` gitignored at both levels, only `.env.example` tracked; the
  `Zone.Identifier` sidecar files are correctly left untracked.
- **Live DB consistent**: 0 stock-sum mismatches, 0 orphan details, 0 orphan returns, 0
  duplicate return keys, 0 approved-return/loan-status mismatches, no negative stock.

## Working tree / deployment state

- Branch `main`, ahead of `origin/main` 4 (`d6d0290`, `ec5498d`, `04412ae`, `03bcf2b`).
  Nothing committed during this audit.
- 25 modified + 12 untracked entries — the untracked set is the entire
  `app/Http/Controllers/API/...`, `app/Http/Requests/...`, `app/Http/Resources/...` layer
  plus `HitungPeminjamanTelat.php`.
- `docker-compose.yml` has no queue worker and no scheduler (N-12).
- `Dockerfile` uses `php:8.4-cli` and the compose service runs `php artisan serve` — a
  dev server, not production-grade.

## Recommended order

1. **Fix N-01 first** — one line, unblocks the edit-peminjaman FormRequest and makes the
   feature actually use the ownership check it was written for.
2. **Extract one shared stock/state service** and route web + API approval, return and
   delete through it. N-02, N-03, N-05, N-11, N-13, N-15 and N-23 all collapse into this.
3. **Add the missing DB constraints** — unique `(peminjaman_id, alat_id)` on
   `detail_pinjam`, partial unique on pending edit requests (N-17, N-18).
4. **Fix the API auth surface** — LoginRequest/RegisterRequest rules, `throttleApi`,
   role alignment (N-07, N-08, N-09).
5. **Fix the seeders** and add a fresh-seed consistency test (N-04).
6. **Add queue worker + scheduler to compose** (N-12).
7. **Guard the API deletes** and switch the history FKs to restrict (N-06).
8. **Close the test gaps** — API endpoints (0 tests today), concurrency, seeder output,
   the `PeminjamanResource` payload (N-10), and the overdue command without `actingAs()`
   (N-19).

## Incident note (data loss, recovered)

During this audit a dispatched subagent ran a probe script (`_recovery/proof_seed.php:23`)
that called `Artisan::call('migrate:fresh', ...)` with `putenv('DB_CONNECTION=sqlite')`.
Laravel was already bootstrapped, so the `putenv` was inert and the fresh-migrate hit the
**live MySQL** database — dropping all 18 tables (`DROP TABLE` verified in
`binlog.000018`). Live data at that moment: 3 users, 6 alat, 16 peminjaman, 15
pengembalian, 73 notifications, 16 jobs, 345 log_aktivitas rows.

Recovery from the binary logs: all 18 binlogs were copied out of the container to
`_recovery/binlogs/`, decoded with `mariadb-binlog` (133,441 row events), replayed in
order applying INSERT/UPDATE/DELETE, and pruned for cascade-orphans to reproduce the
CASCADE deletes mariadb-binlog does not emit. The resulting state matched the pre-wipe
live snapshot exactly (users 3, kategori 6, alat 6, peminjaman 16, detail_pinjam 23,
pengembalian 15, notifications 73, jobs 16, log_aktivitas 345, permintaan_edit 1).

Post-apply verification: 0 stock-sum mismatches, 0 orphans, 0 duplicate returns, 0 status
mismatches, no negative stock; `php artisan test` → 32 passed / 92 assertions. Recovery
artifacts kept at `_recovery/` (`binlog_all.txt`, `recovered_final.sql`).

The subagent probe also left a stale `bootstrap/cache/config.php` that caused 16 test
failures until cleared with `php artisan config:clear`; the cache is regenerated on
demand and the suite is green again.

**Root cause:** the probe relied on `putenv` to redirect the DB connection after Laravel
had already bootstrapped. Any future probing must isolate the connection by
bootstrapping a fresh application with the sqlite config injected *before*
`bootstrap/app.php` loads, or simply run inside a `RefreshDatabase` test where
`phpunit.xml` already forces sqlite.
