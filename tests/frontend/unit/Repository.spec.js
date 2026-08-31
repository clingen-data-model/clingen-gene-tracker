import moment from 'moment'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import Repository from '../../../resources/assets/js/repositories/Repository'
import OmimRepository from '../../../resources/assets/js/repositories/OmimRepository'

describe('Repository', () => {
    beforeEach(() => {
        window.axios = vi.fn()
        Repository.baseUrl = '/api/things'
        Repository.name = 'Things'
        Repository.dates = ['occurred_at']
    })

    it('passes the request method, URL, and payload to the Axios boundary', async () => {
        window.axios.mockResolvedValue({ data: {} })
        const payload = { name: 'Example' }

        await Repository.makeRequest('patch', '/api/things/4', payload)

        expect(window.axios).toHaveBeenCalledWith({
            method: 'patch',
            url: '/api/things/4',
            data: payload,
        })
    })

    it('finds a record and converts configured date fields to Moment values', async () => {
        window.axios.mockResolvedValue({
            data: {
                id: 4,
                occurred_at: '2025-01-02 03:04:05',
                name: 'Example',
            },
        })

        const response = await Repository.find(4, { include: 'owner' })

        expect(window.axios).toHaveBeenCalledWith({
            method: 'get',
            url: '/api/things/4?include=owner',
        })
        expect(moment.isMoment(response.data.occurred_at)).toBe(true)
        expect(response.data.occurred_at.format('YYYY-MM-DD HH:mm:ss')).toBe('2025-01-02 03:04:05')
        expect(response.data.name).toBe('Example')
    })

    it('stores a record and transforms configured dates in the response', async () => {
        const payload = { name: 'New thing' }
        window.axios.mockResolvedValue({
            data: { id: 5, name: 'New thing', occurred_at: '2025-02-03 04:05:06' },
        })

        const response = await Repository.store(payload)

        expect(window.axios).toHaveBeenCalledWith({
            method: 'post',
            url: '/api/things',
            data: payload,
        })
        expect(moment.isMoment(response.data.occurred_at)).toBe(true)
    })

    it('updates and destroys records with the existing URL and payload contracts', async () => {
        window.axios.mockResolvedValue({ data: {} })
        const payload = { id: 8, name: 'Updated thing' }

        await Repository.update(payload)
        await Repository.destroy(8)

        expect(window.axios).toHaveBeenNthCalledWith(1, {
            method: 'put',
            url: '/api/things/8',
            data: payload,
        })
        expect(window.axios).toHaveBeenNthCalledWith(2, {
            method: 'delete',
            url: '/api/things/8',
        })
    })

    it('serializes Moment and configured date values before writing', async () => {
        Repository.dates = ['occurred_at', 'reviewed_at']
        window.axios.mockResolvedValue({ data: {} })
        const payload = {
            id: 9,
            occurred_at: moment('2025-03-04 05:06:07'),
            reviewed_at: '2025-04-05 06:07:08',
        }

        await Repository.save(payload)

        expect(payload).toEqual({
            id: 9,
            occurred_at: '2025-03-04 05:06:07',
            reviewed_at: '2025-04-05 06:07:08',
        })
        expect(window.axios).toHaveBeenCalledWith({
            method: 'put',
            url: '/api/things/9',
            data: payload,
        })
    })
})

describe('OmimRepository', () => {
    beforeEach(() => {
        window.axios = vi.fn().mockResolvedValue({ data: {} })
    })

    it('uses the existing gene and curation request URLs', async () => {
        vi.spyOn(console, 'log').mockImplementation(() => {})

        await OmimRepository.gene('BRCA1')
        await OmimRepository.forCuration(42)

        expect(window.axios).toHaveBeenNthCalledWith(1, {
            method: 'get',
            url: '/api/omim/gene/BRCA1',
        })
        expect(window.axios).toHaveBeenNthCalledWith(2, {
            method: 'get',
            url: '/api/omim/curation/42',
        })
    })

    it.each(['all', 'store', 'update', 'destroy'])('rejects the unsupported %s operation', operation => {
        expect(() => OmimRepository[operation]({})).toThrow('method is not supported')
        expect(window.axios).not.toHaveBeenCalled()
    })
})
