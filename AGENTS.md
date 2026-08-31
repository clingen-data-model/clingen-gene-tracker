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

```vue
:model-value="value"
@update:model-value="value = $event"
```

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

## Next modernization steps

Do not combine major migrations.

Potential future modernization:

1. Vuex → Pinia, if desired later.

Treat any state-management migration as a separate project.

Do not combine a Vuex → Pinia migration with unrelated frontend redesign, routing changes, build-tool changes, or business-logic refactors.
