<template>
    <div>
        <h4 class="d-flex justify-content-between">
            <div><slot name="title">Notes</slot></div>
            <!-- <button class="btn btn-sm btn-primary" @click="showAddForm = true">Add Note</button> -->
        </h4>
        <div v-if="notes.length == 0" class="alert alert-light border">
            Not administrative notes for this record.
        </div>
        <table class="table table-striped"  v-if="notes.length > 0">
            <thead>
                <tr>
                    <th>Note</th>
                    <th>Author</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="note in notes" :key="note.id">
                    <td>{{note.topic ? note.topic.toUpperCase()+': ' : ''}} {{note.content}}</td>
                    <td>{{note.author.name}}</td>
                    <td>{{formatDate(note.created_at)}}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script setup>
import { formatDate } from '../filters'

defineOptions({
    name: 'NotesList',
})

defineProps({
    notes: {
        type: Array,
        required: true
    }
})
</script>
