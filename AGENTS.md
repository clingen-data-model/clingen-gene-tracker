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

## Curation composables

Shared curation behavior uses Vue 3 composables:

- `useCurationForm()`
- `usePhenotypeList()`

Use these existing composables rather than recreating shared synchronization or phenotype-loading logic.

Keep component-specific watchers and business behavior local to the component.

Do not add component-specific behavior to shared composables unless it is genuinely reusable.

## Next modernization steps

Do not combine major migrations.

Potential future phases should be handled separately:

## Next modernization steps

Do not combine major migrations.

Potential future modernization:

1. Vuex → Pinia, if desired later

Do not combine state-management migration with unrelated frontend redesign.

Do not migrate build tooling and state management in the same phase.

## Frontend build

- Vite is the frontend build system.
- Laravel integration uses `laravel-vite-plugin`.
- Vue SFC compilation uses `@vitejs/plugin-vue`.
- Main entries:
  - `resources/assets/js/app.js`
  - `resources/assets/sass/app.scss`
- Blade should load frontend assets with `@vite(...)`.
- Do not reintroduce Laravel Mix, Webpack-specific configuration, or `mix()`.
- Use `import.meta.env` for browser-side environment values.