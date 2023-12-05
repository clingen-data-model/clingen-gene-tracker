export default function (error) {
    return error.response && [401, 402, 403].includes(error.response.status)
}