import moment from 'moment'

export default function formatDate(dateString, format = 'YYYY-MM-DD HH:mm') {
    if (dateString === null) {
        return null;
    }

    return moment(dateString).format(format)
}
