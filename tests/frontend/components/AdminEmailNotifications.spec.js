import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AdminEmails from '../../../resources/assets/js/components/admin/Emails.vue'
import AdminNotifications from '../../../resources/assets/js/components/admin/Notifications.vue'
import EmailBodyPreview from '../../../resources/assets/js/components/admin/EmailBodyPreview.vue'

function mountPage(component) {
    return mount(component, { global: { stubs: {
        'b-alert': { template: '<div><slot /></div>' },
        'b-card': { template: '<section><slot /></section>' },
        'b-button': { template: '<button><slot /></button>' },
        'b-badge': { template: '<span><slot /></span>' },
        'b-pagination': true,
        'b-table': {
            props: ['items', 'fields'],
            template: `<table><tbody><tr v-for="item in items" :key="item.id">
                <td v-for="field in fields" :key="field.key">
                    <slot :name="'cell(' + field.key + ')'" :item="item">{{ item[field.key] }}</slot>
                </td></tr></tbody></table>`,
        },
    } } })
}

beforeEach(() => {
    window.confirm = vi.fn(() => true)
    window.axios = { get: vi.fn(), delete: vi.fn() }
})

describe('Email administration', () => {
    it('renders a paginated read-only log and isolates HTML in details', async () => {
        const email = { id: 4, from: { 'sender@example.com': 'Sender' }, to: { 'recipient@example.com': null }, subject: 'Logged email', created_at: '2026-01-01T12:00:00Z' }
        window.axios.get
            .mockResolvedValueOnce({ data: { data: [email], total: 1 } })
            .mockResolvedValueOnce({ data: { ...email, body: '<p>Hello <strong>reader</strong></p>', cc: null } })
        const wrapper = mountPage(AdminEmails)
        await flushPromises()

        expect(wrapper.text()).toContain('Sender <sender@example.com>')
        expect(wrapper.text()).toContain('Logged email')
        expect(wrapper.text()).not.toContain('Delete')
        await wrapper.findAll('button').find(button => button.text() === 'View').trigger('click')
        await flushPromises()
        expect(window.axios.get).toHaveBeenLastCalledWith('/api/admin/emails/4')
        expect(wrapper.get('iframe').attributes('srcdoc')).toContain('<p>Hello <strong>reader</strong></p>')
        expect(wrapper.find('pre').exists()).toBe(false)
        expect(wrapper.find('strong').exists()).toBe(false)
    })

    it.each([null, '', '  \n '])('handles an empty body (%j)', body => {
        const wrapper = mount(EmailBodyPreview, { props: { body } })
        expect(wrapper.text()).toBe('No email body available.')
        expect(wrapper.find('iframe').exists()).toBe(false)
    })

    it('preserves the layout and inline styles of a complete HTML email', () => {
        const body = '<!doctype html><html><head><style>td { color: navy; }</style></head><body><table><tr><td style="padding: 12px">Hello reader</td></tr></table></body></html>'
        const wrapper = mount(EmailBodyPreview, { props: { body } })
        const preview = new DOMParser().parseFromString(wrapper.get('iframe').attributes('srcdoc'), 'text/html')
        expect(preview.querySelector('td').textContent).toBe('Hello reader')
        expect(preview.querySelector('td').style.padding).toBe('12px')
        expect(preview.head.querySelector('meta[http-equiv="Content-Security-Policy"]')).not.toBeNull()
    })

    it('preserves plain text, newlines, and address brackets', () => {
        const body = 'Hello <person@example.com> and <a@example.com>\n\n  2 < 3 & 4 > 1'
        const wrapper = mount(EmailBodyPreview, { props: { body } })
        expect(wrapper.get('pre').element.textContent).toBe(body)
        expect(wrapper.find('iframe').exists()).toBe(false)
    })

    it('removes active content and keeps an opaque, fully sandboxed preview with restrictive CSP', () => {
        const body = `<p onclick="parent.compromised = true">Hello</p>
            <script>parent.compromised = true</script>
            <img src="https://example.com/tracker" onerror="parent.compromised = true">
            <a href="javascript:parent.compromised=true" target="_top">Link</a>
            <form action="/danger"><button>Submit</button></form>
            <meta http-equiv="refresh" content="0;url=https://example.com">
            <iframe srcdoc="danger"></iframe>`
        const wrapper = mount(EmailBodyPreview, { props: { body } })
        const frame = wrapper.get('iframe')
        expect(frame.attributes('sandbox')).toBe('')
        expect(frame.attributes('referrerpolicy')).toBe('no-referrer')
        const preview = new DOMParser().parseFromString(frame.attributes('srcdoc'), 'text/html')
        expect(preview.querySelector('p').textContent).toBe('Hello')
        expect(preview.querySelector('script, form, button, iframe, [onclick], [onerror], [href], [src]')).toBeNull()
        expect(preview.querySelector('meta[http-equiv="refresh"]')).toBeNull()
        expect(preview.querySelector('meta[http-equiv="Content-Security-Policy"]').content).toContain("default-src 'none'; script-src 'none'")
        expect(wrapper.find('script').exists()).toBe(false)
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

    it.each([
        [{ curation_id: 123, gene_symbol: 'BRCA1' }, '{\n  "curation_id": 123,\n  "gene_symbol": "BRCA1"\n}'],
        ['{"curation_id":123}', '{\n  "curation_id": 123\n}'],
        [[1, { message: 'hello' }], '[\n  1,\n  {\n    "message": "hello"\n  }\n]'],
        ['[1,2]', '[\n  1,\n  2\n]'],
        ['plain\ntext', 'plain\ntext'],
        ['{"broken":', '{"broken":'],
        [null, 'No payload available.'],
        ['null', 'No payload available.'],
        ['', 'No payload available.'],
        ['{"html":"<b>payload</b>"}', '{\n  "html": "<b>payload</b>"\n}'],
        ['<img src=x onerror="unsafe()">', '<img src=x onerror="unsafe()">'],
    ])('formats detail payload %j safely', async (data, expected) => {
        const notification = { id: 'one', readable_type: 'Notice', recipient: null, read_at: null, data }
        window.axios.get.mockResolvedValueOnce({ data: { data: [notification], total: 1 } })
            .mockResolvedValueOnce({ data: notification })
        const wrapper = mountPage(AdminNotifications)
        await flushPromises()
        await wrapper.findAll('button').find(button => button.text() === 'View').trigger('click')
        await flushPromises()
        expect(wrapper.get('pre').element.textContent).toBe(expected)
        expect(wrapper.find('pre b, pre img, pre script').exists()).toBe(false)
    })
})
