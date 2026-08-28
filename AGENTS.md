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
- Laravel Mix remains the build system.

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