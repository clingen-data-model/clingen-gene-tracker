<style></style>
<template>
    <div class="collapsible">
        <h4 class="clearfix">
            <button type="button" class="float-right btn btn-link btn-xs" @click="expanded = !expanded">
                {{ (expanded) ? 'hide' : 'show' }}
            </button>
            <slot name="heading"></slot>
        </h4>
        <b-collapse v-model="expanded" :id="id">
            <slot name="body"></slot>
        </b-collapse>
    </div>
</template>
<script>
    export default {
        props: ['id'],
        data: function() {
            return {
                expanded: true
            }
        }
    }
</script>