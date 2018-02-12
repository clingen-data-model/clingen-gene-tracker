<style></style>
<template>
    <div class="clingen-app-container container">

        <b-card>
            <template slot="header">
                <button 
                    id="new-gene-btn" 
                    class="btn btn-secondary float-right btn-sm" 
                    @click="toggleNewTopicForm()"
                >
                    Add new topic
                </button>
                 
                <h3>Topics in curation</h3>
            </template>
            <topics-table></topics-table>
        </b-card>

        <b-modal 
            v-model="newTopicFormVisible" 
            title="Add a gene to curate"
            hide-footer
            ok-title="Save"
        >
            <new-gene-form 
                @new-gene-saved="toggleNewTopicForm()"
                @new-gene-canceled="toggleNewTopicForm()"
            >
            </new-gene-form>
        </b-modal>
    </div>
</template>
<script>
    export default {
        components: {
            'new-gene-form': require('./NewTopicForm.vue'),
            'topics-table': require('./TopicsTable.vue'),
        },
        data: function() {
            return {
                'newTopicFormVisible': false,
            }
        },
        computed: {
        },
        methods: {
            toggleNewTopicForm: function () {
                this.newTopicFormVisible = !this.newTopicFormVisible;
            }
        },
        mounted: function () {
        }
    }
</script>