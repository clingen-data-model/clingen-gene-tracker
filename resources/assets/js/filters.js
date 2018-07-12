import Vue from 'vue'
import moment from 'moment'

Vue.filter('formatDate', function (dateString, format = 'YYYY-MM-DD HH:mm') {
    if (dateString === null) {
        return null;
    }
    
    return moment(dateString).format(format)
})