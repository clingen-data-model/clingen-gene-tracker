## Vue frontend

Current state:

- Native Vue 3.5.
- All scripted Vue SFCs use Composition API with `<script setup>`.
- No Options API SFCs remain.
- No Vue 2 compatibility APIs or `@vue/compat` remain.
- No Vue mixins remain.
- Vue Router 4 is in use.
- Vuex 4 is in use.
- BootstrapVueNext + Bootstrap 5 are in use.
- Vite is the frontend build system.

## Vue development conventions

For new or modified Vue components:

- Use `<script setup>`.
- Use Vue 3 Composition API patterns.
- Use `defineProps()` and `defineEmits()`.
- Use `ref`, `reactive`, `computed`, and `watch` as appropriate.
- Use `useStore()` for existing Vuex access.
- Use `useRoute()` / `useRouter()` for router access.
- Do not introduce Options API components.
- Do not introduce Vue mixins.
- Do not reintroduce Vue 2 APIs or `@vue/compat`.

Keep component-specific behavior local unless it is genuinely reusable.

Do not broaden a focused migration or bug fix into unrelated frontend refactoring.

## Vue model bindings

All component model contracts use Vue 3 semantics:

- `modelValue`
- `update:modelValue`

Explicit bindings are preferred when clarity helps:

    :model-value="value"
    @update:model-value="value = $event"

Normal Vue 3 `v-model` is also valid.

Do not reintroduce Vue 2 `value` / `input` component model contracts.

## Curation composables

Shared curation behavior uses Vue 3 composables:

- `useCurationForm()`
- `usePhenotypeList()`

Use these existing composables rather than recreating shared synchronization or phenotype-loading logic.

Keep component-specific watchers and business behavior local to the component.

Do not add component-specific behavior to shared composables unless it is genuinely reusable.

## Frontend build

- Vite is the frontend build system.
- Laravel integration uses `laravel-vite-plugin`.
- Vue SFC compilation uses `@vitejs/plugin-vue`.
- Vite configuration lives in `vite.config.mjs`.
- Main entries:
  - `resources/assets/js/app.js`
  - `resources/assets/sass/app.scss`
- Blade should load frontend assets with `@vite(...)`.
- Use `import.meta.env` for browser-side environment values.
- Do not reintroduce Laravel Mix, Webpack-specific configuration, `webpack.mix.js`, or Blade `mix()`.
- Do not hard-code generated Vite asset filenames.
- Generated files under `public/build/**` are build output and should not be treated as source files.

## Sass conventions

Application-owned Sass should use modern Dart Sass module APIs.

- Prefer `@use` / `@forward` over deprecated Sass `@import`.
- Do not modify Sass files under `node_modules`.
- Bootstrap dependency deprecation warnings may be suppressed with `quietDeps`, while application-owned Sass warnings should remain visible.
- Prefer modern Sass APIs such as `color.adjust()` rather than deprecated `darken()` / `lighten()`.
- Preserve existing Bootstrap variable customization and generated CSS behavior when modernizing Sass.

External CSS imports, such as remote font imports, do not need to be converted to Sass modules.

## Vite chunking

The Vite build currently separates major dependencies into vendor groups to keep the application entry small while preserving existing route-level lazy loading.

Current dependency groups include:

- `vue-vendor` for Vue, Vue Router, and Vuex.
- `ui-vendor` for Bootstrap, BootstrapVueNext, Floating UI, VueUse, and Reka UI.
- `vendor` for Axios, Lodash, Moment, and remaining dependencies.

Do not remove or substantially redesign chunking without a concrete reason.

Do not replace existing route lazy loading with eager imports unless required by application behavior.

## Frontend testing

The frontend testing stack includes:

- Vitest
- Vue Test Utils
- jsdom
- Playwright

### Vitest

Frontend unit/component tests live under:

- `tests/frontend/unit`
- `tests/frontend/components`

Run the frontend unit suite with:

    npm run test:unit -- --run

Testing conventions:

- Prefer focused tests around business behavior and public contracts.
- Use explicit Vitest imports rather than global test APIs.
- Keep `tests/frontend/setup.js` minimal.
- Do not import `app.js` as global test setup.
- Mock repository/network boundaries rather than initializing the full application.
- Use small Vuex test stores only when store injection behavior matters.
- Stub BootstrapVueNext components when testing application behavior; do not test BootstrapVueNext itself.
- Use fake timers for debounce/timer behavior where appropriate.
- Prefer deterministic fixtures over production-like data.
- Do not modify production code solely to make tests easier unless testing exposes a genuine behavior or design problem.

Important frontend behavior currently protected includes:

- user/curation permission logic
- `useCurationForm()` synchronization
- repository request/date contracts
- `SearchSelect` interaction behavior
- `usePhenotypeList()` loading behavior
- curation table provider request contracts

Do not introduce coverage thresholds merely to increase a percentage. Add coverage incrementally when changing related functionality.

### Playwright

End-to-end tests live under:

- `tests/e2e`

Run the E2E suite with:

    npm run test:e2e

Current Playwright conventions:

- Chromium is the initial supported E2E browser.
- Use one worker while tests share mutable database state.
- Use the real Laravel login flow.
- Do not add authentication bypasses, forged cookies, or test-only login endpoints.
- Reuse authenticated Playwright storage state from `playwright/.auth/`.
- When permission coverage requires more than one user, authenticate each deterministic seeded user through the real login form and keep each storage-state file under the ignored `playwright/.auth/` directory.
- Authentication state, reports, and test results must remain ignored by Git.
- Prefer role, accessible-name, label, and visible-text selectors.
- Add `data-testid` only when no stable semantic selector exists.
- Prefer Playwright auto-waiting and observable UI/network state.
- Do not use arbitrary `waitForTimeout()` calls for synchronization.
- Monitor unexpected page errors, application-origin console errors, failed application requests, and relevant HTTP 4xx/5xx responses.
- Keep E2E tests deterministic and independent of execution order.

Current E2E coverage includes:

- unauthenticated redirect to login
- real Laravel login
- authenticated storage-state reuse
- privileged and restricted authenticated user states
- Vue application startup
- authenticated navigation visibility
- curation-list rendering
- curation global search
- server-side sorting
- advanced filtering
- archived filtering
- pagination
- curation detail navigation
- deterministic curation creation
- creation validation behavior
- edit-step/hash navigation
- phenotype-driven autosave
- backend persistence after autosave
- duplicate-save prevention
- curation Show-page content
- permission-sensitive Edit/Delete/Transfer visibility
- archived-curation presentation and restrictions
- deterministic GCI-link visibility without external navigation

### E2E database

Playwright uses the dedicated disposable database:

- `genetracker_e2e`

The E2E preparation flow must:

- verify the configured database is exactly `genetracker_e2e`
- refuse to reset any other database
- connect successfully before running destructive migrations
- run migrations and deterministic E2E seeds
- use persistent testing-only sessions

Database provisioning is separate from schema reset:

- Docker may provision the E2E database.
- Local non-Docker developers may provision it once manually.
- `scripts/prepare-e2e.php` must not create databases.

E2E-specific deterministic fixtures may be seeded only from the E2E preparation flow and must not alter normal development or PHPUnit seeding behavior.

Do not run Playwright against a normal development database.

## Notification digests

`send-notifications` emails each user a digest of their unread notifications.

Active users with the `receive all curation digests` permission (direct or via a role) instead receive one combined digest of every user's notifications.

- Grant the permission through the admin Users screen; do not reintroduce an env/config recipient list.
- Roles and permissions are created by `RolesAndPermissionsSeeder`, which deploy re-runs; new roles/permissions do not need migrations.

## Next modernization steps

Do not combine major migrations.

The Vue 3, Vite, Sass, Vitest, and Playwright modernization foundation is complete.

## Command resource usage

`App\Listeners\RecordCommandResourceUsage` records CPU time and peak memory of each top-level artisan process in `command_resource_usages`, for tuning scheduled-task resource requests.

- Nested `Artisan::call()` commands are attributed to the outer command.
- `schedule:run` is not recorded; scheduled commands run as separate processes and are recorded individually.
- The listener must stay a container singleton (bound in `EventServiceProvider::register()`); it tracks nesting across events.
- Disable with `RECORD_COMMAND_RESOURCE_USAGE=false`; it is disabled in `phpunit.xml`.
- Rows are inserted when a command starts (usage columns null until it finishes), so in-progress and crashed runs are visible.

## Scheduling

Deployment to OpenShift is via the Helm chart in https://github.com/clingen-data-model/cgwi-php-helm. Its scheduler CronJob runs `schedule:run` infrequently rather than every minute, so scheduled tasks catch up instead of matching the exact minute.

- `App\Console\Kernel::resolveConsoleSchedule()` builds a `CatchUpSchedule`, whose `exec()` (mirrored from Laravel's `Schedule::exec()`) creates `CatchUpEvent`s; check both on Laravel upgrades.
- A `CatchUpEvent` is due when its expression's most recent due time is later than its command's latest `command_resource_usages.started_at`; it runs once per invocation however many slots were missed.
- Tasks are matched by artisan command name (arguments ignored); manual runs and failed runs count.
- Commands with no recorded runs use the table's earliest row as their last run.
- With recording disabled or no recorded runs at all, events fall back to Laravel's exact-minute matching.
- Callback/job events (`call()`, `job()`) are not affected.
- Keep `Kernel::schedule()` expressions as the intended run times.
