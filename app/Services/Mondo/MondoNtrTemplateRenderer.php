<?php

namespace App\Services\Mondo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class MondoNtrTemplateRenderer
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function render(array $payload): string
    {
        $cachePath = config('mondo.template.cache_path');

        if (!Storage::disk('local')->exists($cachePath)) {
            throw new \RuntimeException("Template not cached. Run: php artisan mondo:sync-ntr-template");
        }

        $templateAbs = storage_path('app/'.$cachePath);

        $jsonAbs = storage_path('app/mondo/tmp/ntr_payload.json');
        $outAbs  = storage_path('app/mondo/tmp/ntr_issue_body.md');
        @mkdir(dirname($jsonAbs), 0775, true);

        file_put_contents($jsonAbs, json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        @unlink($outAbs);

        $python = base_path(config('mondo.renderer.python_bin'));
        $process = new Process([
            $python,
            base_path('scripts/render_jinja.py'),
            $templateAbs,
            $jsonAbs,
            $outAbs,
        ]);

        $process->setTimeout(15);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                "Render failed:\nSTDERR:\n".$p->getErrorOutput()."\nSTDOUT:\n".$p->getOutput()
            );
        }

        return (string) file_get_contents($outAbs);
    }

}
