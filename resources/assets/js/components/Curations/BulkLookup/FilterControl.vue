<template>
    <div class="mb-3">
        <h6>{{title}}</h6>
        <ul class="list-group text-small">
            <li class="list-group-item cursor-pointer"
                v-for="item in items" :key="item"
                @click="$emit('toggle', item)"
                :class="{selected: selectedFilters.includes(item)}"
            >
                <span class="btn btn-sm float-end cursor-pointer"
                    v-if="selectedFilters.includes(item)"
                >
                    x
                </span>
                {{item}}
            </li>
        </ul>
    </div>

</template>
<script>
    export default {
        props: {
            items: {
                type: Array,
                required: true
            },
            title: {
                type: String,
                default: 'Filter'
            },
            selectedFilters: {
                type: Array,
                required: true
            }
        },
        emits: ['toggle'],
    }
</script>
<style>
    li.selected {
        text-decoration: underline;
        font-weight: bold;
    }
</style>
