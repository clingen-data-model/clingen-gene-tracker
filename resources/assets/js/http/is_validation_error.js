export default function (error) {
    return error.response && error.response.status == 422 && error.response.data.errors
}