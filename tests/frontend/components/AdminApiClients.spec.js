import { flushPromises, mount } from '@vue/test-utils'
import { createBootstrap } from 'bootstrap-vue-next'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import registerBootstrapVueNext from '../../../resources/assets/js/bootstrap-vue-next'
import AdminApiClients from '../../../resources/assets/js/components/admin/ApiClients.vue'

function mountPage() { return mount(AdminApiClients, { global: { plugins: [createBootstrap(), { install: registerBootstrapVueNext }] } }) }
const client = { id: 5, uuid: 'safe-uuid', name: 'External Consumer', contact_email: 'owner@example.com', tokens_count: 1, tokens: [{ id: 8, name: 'production', created_at: '2026-01-01T00:00:00Z', last_used_at: null }] }

beforeEach(() => {
    window.confirm = vi.fn(() => true)
    Object.assign(navigator, { clipboard: { writeText: vi.fn() } })
    window.axios = { get: vi.fn().mockResolvedValue({ data: { data: [client], total: 1 } }), post: vi.fn(), put: vi.fn(), delete: vi.fn() }
})

describe('API Client administration', () => {
    it('renders clients and token metadata without exposing a stored secret', async () => {
        window.axios.get.mockResolvedValueOnce({ data: { data: [client], total: 1 } }).mockResolvedValueOnce({ data: client })
        const wrapper = mountPage(); await flushPromises()
        expect(wrapper.text()).toContain('External Consumer')
        expect(wrapper.text()).toContain('owner@example.com')
        await wrapper.findAll('button').find(button => button.text() === 'View').trigger('click'); await flushPromises()
        expect(wrapper.text()).toContain('production')
        expect(wrapper.text()).not.toContain('plain_text_token')
    })

    it('shows backend client validation', async () => {
        window.axios.post.mockRejectedValue({ response: { status: 422, data: { errors: { name: ['The name has already been taken.'] } } } })
        const wrapper = mountPage(); await flushPromises()
        await wrapper.findAll('button').find(button => button.text() === 'Add API Client').trigger('click')
        await wrapper.find('form').trigger('submit'); await flushPromises()
        expect(wrapper.text()).toContain('The name has already been taken.')
    })

    it('presents a newly created plaintext token once and confirms scoped revocation', async () => {
        window.axios.get.mockResolvedValueOnce({ data: { data: [client], total: 1 } }).mockResolvedValue({ data: client })
        window.axios.post.mockResolvedValue({ data: { plain_text_token: 'one-time-secret', token: { id: 9, name: 'new' } } })
        window.axios.delete.mockResolvedValue({})
        const wrapper = mountPage(); await flushPromises()
        await wrapper.findAll('button').find(button => button.text() === 'View').trigger('click'); await flushPromises()
        await wrapper.get('#token-name').setValue('new')
        await wrapper.findAll('form').find(form => form.find('#token-name').exists()).trigger('submit'); await flushPromises()
        expect(wrapper.text()).toContain('one-time-secret')
        await wrapper.findAll('button').find(button => button.text() === 'Revoke').trigger('click'); await flushPromises()
        expect(window.confirm).toHaveBeenCalledWith('Revoke token production?')
        expect(window.axios.delete).toHaveBeenCalledWith('/api/admin/api-clients/5/tokens/8')
        expect(wrapper.text()).toContain('Token revoked successfully.')
    })
})
