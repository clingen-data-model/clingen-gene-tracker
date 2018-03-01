<style></style>
<template>
    <div class="new-topic-container">
        <b-card>
            <template slot="header">
                <h3>Add a topic to curate</h3>
            </template>
            <new-topic-form 
                @new-topic-saved="$router.push('/')"
                @new-topic-canceled="$router.push('/')"
            >
            </new-topic-form>
        </b-card>
    </div>
</template>
<script>
    export default {
        components: {
            'new-topic-form': require('./NewTopicForm.vue'),
        }
        //component definition
    }
</script>