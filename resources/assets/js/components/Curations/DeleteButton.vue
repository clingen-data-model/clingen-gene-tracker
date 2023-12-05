<style></style>

<template>
    <button 
        v-if="user.canDeleteCuration(curation)"
        :id="'delete-curation-'+curation.id+'-btn'"
        class="btn btn-danger"
        @click="deleteCuration(curation)"
    >
        <slot>Delete</slot>
    </button>
</template>

<script>
    import { mapMutations, mapActions, mapGetters } from 'vuex'

    export default {
        props: {
            curation: {
                required: true,
                type: Object
            }
        },
        data() {
            return {
            }
        },
        computed: {
            ...mapGetters({user: 'getUser'}),
            title: function () {
                let title = '';
                if (this.curation && this.curation.gene_symbol) {
                    title += this.curation.gene_symbol
                    if (this.curation.mondo_id) {
                        title += ' / ' + this.curation.mondo_id
                    }
                    if (this.curation.expert_panel) {
                        title += ' for '+this.curation.expert_panel.name
                    }
                }
                return title;
            },
        },
        methods: {
            ...mapActions('curations', {
                destroyCuration: 'destroyItem'
            }),
           ...mapMutations('messages', [
                'addInfo',
                'addError'
            ]),
            deleteCuration() {
                if (confirm("You're about to delete "+this.title+'. This can not be undone.  Are you sure you want to continue?')) {
                    this.$router.push('/')
                    const title = this.title
                    this.destroyCuration(this.curation.id)
                        .then(response => {
                            this.addInfo(title + ' was successfully deleted.')
                        })
                        .catch((error) => {
                            let msg = 'There was a problem deleting'+title;
                            if (error.response.status == 403) {
                                msg = 'You do not have permissions to delete curations.  Please contact an adminstrator to help you delete the curation.';
                            }
                            this.addError(msg)
                        });
                }
            }
        }
    
}
</script>