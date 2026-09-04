import { flushPromises, mount } from '@vue/test-utils'
import { createBootstrap } from 'bootstrap-vue-next'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import registerBootstrapVueNext from '../../../resources/assets/js/bootstrap-vue-next'
import AdminEmails from '../../../resources/assets/js/components/admin/Emails.vue'
import AdminNotifications from '../../../resources/assets/js/components/admin/Notifications.vue'

function mountPage(component) {
    return mount(component, { global: { plugins: [createBootstrap(), { install: registerBootstrapVueNext }] } })
}

beforeEach(() => {
    window.confirm = vi.fn(() => true)
    window.axios = { get: vi.fn(), delete: vi.fn() }
})

describe('Email administration', () => {
    it('renders a paginated read-only log and escapes body markup in details', async () => {
        const email = { id: 4, from: { 'sender@example.com': 'Sender' }, to: { 'recipient@example.com': null }, subject: 'Logged email', created_at: '2026-01-01T12:00:00Z' }
        window.axios.get
            .mockResolvedValueOnce({ data: { data: [email], total: 1 } })
            .mockResolvedValueOnce({ data: { ...email, body: '<script>unsafe()</script>', cc: null } })
        const wrapper = mountPage(AdminEmails)
        await flushPromises()

        expect(wrapper.text()).toContain('Sender <sender@example.com>')
        expect(wrapper.text()).toContain('Logged email')
        expect(wrapper.text()).not.toContain('Delete')
        await wrapper.findAll('button').find(button => button.text() === 'View').trigger('click')
        await flushPromises()
        expect(window.axios.get).toHaveBeenLastCalledWith('/api/admin/emails/4')
        expect(wrapper.get('pre').text()).toBe('<script>unsafe()</script>')
        expect(wrapper.find('pre script').exists()).toBe(false)
    })
})

describe('Notification administration', () => {
    it('renders recipient/read state, safely formats payload, and confirms deletion', async () => {
        const notification = { id: 'notice-1', readable_type: 'ExampleNotice', recipient: { name: 'Recipient' }, read_at: null, data: { message: '<b>payload</b>' }, created_at: '2026-01-01T12:00:00Z' }
        window.axios.get
            .mockResolvedValueOnce({ data: { data: [notification], total: 1 } })
            .mockResolvedValueOnce({ data: notification })
            .mockResolvedValueOnce({ data: { data: [], total: 0 } })
        window.axios.delete.mockResolvedValue({})
        const wrapper = mountPage(AdminNotifications)
        await flushPromises()

        expect(wrapper.text()).toContain('Recipient')
        expect(wrapper.text()).toContain('Unread')
        await wrapper.findAll('button').find(button => button.text() === 'View').trigger('click')
        await flushPromises()
        expect(wrapper.get('pre').text()).toContain('<b>payload</b>')
        expect(wrapper.find('pre b').exists()).toBe(false)

        await wrapper.findAll('button').find(button => button.text() === 'Delete').trigger('click')
        await flushPromises()
        expect(window.confirm).toHaveBeenCalled()
        expect(window.axios.delete).toHaveBeenCalledWith('/api/admin/notifications/notice-1')
        expect(wrapper.text()).toContain('Notification deleted successfully.')
    })

    it('handles null and malformed payloads without failing', async () => {
        window.axios.get.mockResolvedValueOnce({ data: { data: [
            { id: 'one', readable_type: 'NullNotice', recipient: null, read_at: null, data: null },
            { id: 'two', readable_type: 'LegacyNotice', recipient: null, read_at: null, data: 'not-json' },
        ], total: 2 } })
        const wrapper = mountPage(AdminNotifications)
        await flushPromises()
        expect(wrapper.text()).toContain('NullNotice')
        expect(wrapper.text()).toContain('LegacyNotice')
    })
})
