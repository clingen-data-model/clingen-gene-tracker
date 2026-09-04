<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;

class ReadOnlyLogViewerController extends LogViewerController
{
    protected $view_log = 'admin.logs';

    public function index()
    {
        $request = request();
        abort_if($request->hasAny(['clean', 'del', 'delall']), 405, 'Log modification is disabled.');

        $response = parent::index();
        if (! $response instanceof \Illuminate\View\View) {
            return $response;
        }

        $data = $response->getData();
        if (is_array($data['logs'])) {
            $perPage = 100;
            $page = max((int) $request->input('page', 1), 1);
            $data['logs'] = new LengthAwarePaginator(
                array_slice($data['logs'], ($page - 1) * $perPage, $perPage),
                count($data['logs']),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->except('page')]
            );
        }

        return view($this->view_log, $data);
    }
}
