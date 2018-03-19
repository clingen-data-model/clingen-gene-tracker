<style></style>
<template>
    <div class="test-container">
        <h4>Form Test</h4>
        <p>ID: {{id}}</p>
        <p>Last Changed: {{lastChanged}}</p>
        <div class="row">
            <div class="col-md-4">
                <div v-if="topic">
                    <input type="text" v-model="topic.gene_symbol"></input>
                </div>
            </div>
            <div class="col-md-8 alert alert-secondary"><pre>{{ topic }}</pre></div>
        </div>
    </div>
</template>
<script>
    import { mapGetters, mapActions, mapMutations } from 'vuex'

    export default {
        props: ['id'],
        data () {
            return {
                lastChanged: null,
                topic: _.cloneDeep(this.getTopic(id))
            }
        },
        computed: {
            ...mapGetters('topics', {
                getTopic: 'getItemById'
            })
        },
        methods: {
            ...mapMutations('topics', {
                updateTopic: 'addItem'
            }),
            ...mapActions('topics', {
                fetchTopic: 'fetchItem'
            })
        },
        mounted: function() {
            this.fetchTopic(this.id)
                .then((response) => this.$forceUpdate())
                .catch(error => console.log(error));
        }
    }
</script>