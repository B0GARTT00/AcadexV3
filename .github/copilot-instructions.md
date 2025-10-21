# Acadex Copilot Instructions

## 🏗️ Architecture
- Laravel 12 monolith with Blade views (`resources/views`) and a Vite pipeline (`resources/js`, `vite.config.js`); AlpineJS and Bootstrap are the default UI stack.
- Domain models revolve around `Student`, `Subject`, `TermGrade`, `FinalGrade`, and `Activity`, all using manual `is_deleted` flags instead of `SoftDeletes`.
- Global `NoCacheHeaders` middleware prevents browser caching, and `DashboardController@index` dispatches to role-specific dashboards by checking Gates.

## 🔐 Roles & periods
- Role integers live in `App\Providers\AuthServiceProvider`: instructor=0, chairperson=1, dean=2, admin=3; use `Gate::authorize('role')` helpers to guard controllers.
- Instructor tooling requires `session('active_academic_period_id')`; the `academic.period.set` middleware alias (registered in `bootstrap/app.php`) redirects to `/select-academic-period` until that session key is set.
- `routes/web.php` groups routes by portal; reuse its middleware stacks when adding endpoints to keep role and period enforcement consistent.

## 🧮 Grades & activities
- `App\Traits\GradeCalculationTrait` applies weights (quiz 40%, OCR 20%, exam 40%) and expects seven activities per term; the companion `ActivityManagementTrait` seeds 3 quizzes, 3 OCRs, 1 exam when missing.
- Term labels map to IDs via `getTermId` helpers (`prelim`→1, `midterm`→2, `prefinal`→3, `final`→4); reuse the helpers in grade logic to avoid mismatches.
- `GradeController` saves scores, updates `term_grades`, and triggers `calculateAndUpdateFinalGrade`; any new grading flow must call both term and final update methods to keep averages in sync.

## 📥 Imports
- `StudentImportController` stages Excel uploads into `review_students` using `StudentReviewImport` (columns: last name, first name, middle name, year level, course code).
- Confirming a list deduplicates by name/course/period, links through `StudentSubject`, and ensures default activities exist for every term—mirror this when introducing new intake paths.
- Imports and manual enrollment both depend on `session('active_academic_period_id')`; validate the subject’s period before creating students.

## 🧑‍🏫 Back-office portals
- Chairperson flows in `ChairpersonController` constrain instructors and subjects to the authenticated user’s department/course and active period; they forbid unassigning subjects with enrolled students.
- Admin CRUD in `AdminController` manages departments, courses, subjects, academic periods, and higher-role users, always toggling `is_deleted` instead of removing rows.
- `AcademicPeriodController::generate` auto-creates a new academic year (1st/2nd/Summer); invoke it instead of hand-seeding periods to maintain the `YYYY-YYYY` pattern.

## 📈 Usage analytics
- Login, failed login, and logout listeners (`app/Listeners/LogUser*`) write to `user_logs` and dedupe events within five seconds while capturing device info via `jenssegers/agent`.
- `DashboardController@adminDashboard` and `AdminController@viewUserLogs` rely on this table; extend analytics by adding new listener logic, not controller-side inserts.

## 🛠️ Developer workflows
- Fresh setup: `composer install`, `npm install`, copy `.env`, run `php artisan key:generate`, then migrations/seeds.
- Local dev watcher: `composer run dev` launches PHP serve, queue listener, Laravel Pail, and Vite through `npx concurrently`; on PowerShell you can run the processes separately if concurrency has issues.
- Asset build via `npm run build`; backend tests with `composer test` (clears config cache before `php artisan test`).

## 🧩 Frontend & assets
- Vite entry is `resources/js/app.js`; it imports `bootstrap/dist/css` and starts Alpine. Tailwind is configured in `tailwind.config.js` but legacy Bootstrap styles still apply.
- Blade templates sit under `resources/views/...` (e.g., `dashboard/*`, `instructor/*`); stay consistent with existing component/partial structure when introducing new screens.

## ⚙️ Data conventions
- Always filter active records with `->where('is_deleted', false)`; pivots (`student_subjects`) expose the same flag via the `StudentSubject` model.
- `FinalGrade` and `TermGrade` include `created_by`/`updated_by`; controllers typically set these with `Auth::id()`, so propagate that when writing service logic.
- `User` emails are stored with the `@brokenshire.edu.ph` domain appended in controllers; preserve this convention when creating accounts programmatically.

## 🧪 Testing tips
- Tests extend `Tests\TestCase`; use `RefreshDatabase` for grade/import scenarios to reset `students`, `student_subjects`, `term_grades`, and `final_grades` tables.
- Stub the academic period session in tests (`session(['active_academic_period_id' => $period->id])`) before hitting instructor routes to satisfy the middleware redirect.

## 🚫 Code quality rules
- **Never access properties on potentially null objects**: Always use null-safe operators (`?->`) or helper functions like `optional()` when accessing properties on objects that might be null.
- **Validate before accessing**: Before accessing object properties or array keys, ensure they exist using null checks, `isset()`, or null coalescing operators (`??`).
- **Load relationships defensively**: When accessing relationships, use `loadMissing()` or eager loading to ensure relationships are available before accessing their properties.
- **Safe route generation**: When generating routes with model parameters, verify models exist and use fallback routes if null: `$model ? route('name', $model) : route('fallback')`.
- **Match expressions**: Always include a `default` case in match expressions and ensure all matched values are properly initialized before use.
- **No undefined variables or properties**: When referencing any attribute, method, or variable, declare it explicitly or ensure the model metadata (casts, `$fillable`, PHPDoc) tells static analysis where it comes from; add annotations or refactor code before leaving warnings behind.
- **Document complex flows**: When controller actions or services combine multiple branch-dependent return types, add precise PHPDoc (`@return`, `@var`) or extract helpers so the analyzer can infer types without emitting "internal limitation" warnings.
