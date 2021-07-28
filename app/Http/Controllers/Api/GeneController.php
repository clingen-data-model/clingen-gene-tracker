<?php

namespace App\Http\Controllers\Api;

use App\Gene;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Response;

class GeneController extends Controller
{
    public function index(Request $request)
    {
        return $this->search($request);
    }
 
    
    public function download(Request $request)
    {
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=file.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );
    
        $results = $this->search($request)
                    ->transform(function ($gene) {
                        if ($gene->phenotypes->count() == 0) {
                            return collect([[
                                'Gene' => $gene->gene_symbol,
                                'Phenotype' => null,
                                'MOI' => null
                            ]]);
                        }
                        return $gene->phenotypes->map(function ($pheno, $key) use ($gene) {
                            return [
                                'Gene' => $gene->gene_symbol,
                                'Phenotype' => $pheno->name,
                                'Phenotype MIM Number' => $pheno->mim_number,
                                'MOI' => $pheno->moi
                            ];
                        });
                    })->flatten(1);

        $columns = ['Gene', 'Phenotype', 'Phenotype MIM Number','MOI'];
        $callback = function () use ($results, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
    
            foreach ($results as $result) {
                fputcsv(
                    $file,
                    $result
                );
            }
            fclose($file);
        };
        return Response::stream($callback, 200, $headers);
    }

    private function search(Request $request): Collection
    {
        $query = Gene::query();
        if ($request->where) {
            foreach ($request->where as $key => $value) {
                if (is_string($value)) {
                    $value = explode(',',$value);
                }
                $value = array_filter(array_map(function ($i) { return trim($i); }, $value), function ($i) {
                    return !empty($i);
                });
                $query->whereIn($key, $value);
            }
        }
        if ($request->with) {
            $query->with($request->with);
        }
        if ($request->orderBy) {
            foreach ($request->orderBy as $orderBy) {
                $this->query->orderBy(...$orderBy);
            }
        }

        return $query->get();
    }
    
}
