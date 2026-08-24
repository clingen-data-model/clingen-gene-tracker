<style>
    .form-control[readonly]{
        background: #fff;
    }
</style>
<template>
    <input 
        ref="input"
        type="text" 
        class="form-control" 
        :placeholder="placeholder"
        v-bind:value="formatted" 
        v-on:input="$event.target.value = modelValue" 
        :readonly="readonly"/>
</template>
<script>
    require('../../../../node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker.css')
    
    var moment = require('moment'),
        datepicker = require('bootstrap-datepicker');

    module.exports = {
        name: 'date-field',
        props: ['name', 'modelValue', 'id', 'placeholder', 'readonly'],
        emits: ['update:modelValue'],
        data: function(){
            return {
            }
        },
        computed: {
            formatted: function(){
                return (this.modelValue) ? moment(this.modelValue).format('MM/DD/YYYY') : null;
            },
        },
        mounted: function(){
            this.$nextTick(function(){
                jQuery(this.$el).datepicker()
                    .on('changeDate', function(evt){
                        jQuery(this.$el).trigger('input');
                        this.$emit('update:modelValue', moment(evt.date, 'MM/DD/YYYY').toDate());
                    }.bind(this));
            }.bind(this));
        }
    };
</script>
