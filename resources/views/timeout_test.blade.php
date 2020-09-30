@extends('layouts.app')

@section('content')
    <button class="btn btn-sm btn-info" id="web-data-button">Fetch all web data</button>
    <button class="btn btn-sm btn-primary" id="api-data-button">Fetch all api data</button>
    @php 
        dump($data)
    @endphp
    <hr>
@endsection

@push('scripts')
    <script src="https://momentjs.com/downloads/moment.js"></script>
    <script>
        console.log('push to stack')
        const apiUrls = [
            '/api/timeout-test',
            '/api/auth/timeout-test',
        ]
        
        const webUrls = [
            '/timeout-test',
            '/auth/timeout-test',
        ]
        
        const options = [
            [],
            ['use_db'],
            ['use_cache'],
            ['use_db', 'use_cache'],
        ]

        document.getElementById('api-data-button')
            .addEventListener('click', function (evt) {
                console.info('api', apiUrls);
               fetchData(apiUrls, options); 
            });

        document.getElementById('web-data-button')
            .addEventListener('click', function (evt) {
                console.info('web', webUrls);
               fetchData(webUrls, options); 
            });

        const fetchData = function(urls, options) {
            for (const i in urls) {
                if (urls.hasOwnProperty(i)) {
                    const element = urls[i];
                    for (const j in options) {
                        let queryString = '?timestamp='+moment().format('YYYY-MM-DD\Thh:mm:ss')
                        if (options.length > 0) {
                            queryString = queryString+'&'+options[j].join('&')
                        }
                        console.info('url with options', urls[i]+queryString);
                        window.axios.get(urls[i]+queryString)
                    }
                }
            }
        }
    </script>
@endpush