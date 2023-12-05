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
        v-on:input="$event.target.value = value" 
        :readonly="readonly"/>
</template>
<script>
    require('../../../../node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker.css')
    
    var moment = require('moment'),
        datepicker = require('bootstrap-datepicker');

    module.exports = {
        name: 'date-field',
        props: ['name', 'value', 'id', 'placeholder', 'readonly'],
        data: function(){
            return {
            }
        },
        computed: {
            formatted: function(){
                return (this.value) ? moment(this.value).format('MM/DD/YYYY') : null;
            },
        },
        mounted: function(){
            this.$nextTick(function(){
                jQuery(this.$el).datepicker()
                    .on('changeDate', function(evt){
                        jQuery(this.$el).trigger('input');
                        this.$emit('input', moment(evt.date, 'MM/DD/YYYY').toDate());
                    }.bind(this));
            }.bind(this));
        }
    };
</script>