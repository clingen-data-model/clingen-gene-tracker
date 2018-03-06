<style></style>
<template>
    <div class="new-topic-container">
        <b-card>
            <template slot="header">
                <h3>Add a topic to curate</h3>
            </template>
            <topic-form @canceled="$router.go(-1)"></topic-form>
<!--             <new-topic-form 
                @new-topic-saved="$router.push('/')"
                @new-topic-canceled="$router.push('/')"
            >
            </new-topic-form>
 -->        </b-card>
    </div>
</template>
<script>
    import TopicForm from './Form'
    export default {
        components: {
            'new-topic-form': require('./NewTopicForm.vue'),
            'topic-form': TopicForm
        },
        data: function () {
            return {
                topic: {}
            }
        }
        //component definition
    }
</script>