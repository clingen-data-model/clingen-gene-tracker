import dayjs from 'dayjs'

export function formatDate(dateString, format = 'YYYY-MM-DD HH:mm') {
    if (dateString === null || dateString === undefined) {
        return null
    }

    return dayjs(dateString).format(format)
}
