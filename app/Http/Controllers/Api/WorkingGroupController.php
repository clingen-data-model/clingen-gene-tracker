<?php

namespace App\Http\Controllers\Api;

use App\WorkingGroup;
use App\Curation;
use App\Http\Resources\WorkingGroupResource;
use Illuminate\Support\Facades\File;
use ZipArchive;

class WorkingGroupController extends ApiController
{
    protected $modelClass = WorkingGroup::class;

    public function show($id)
    {
        $group = WorkingGroup::query()
            ->with([
                'expertPanels' => function ($query) {
                    $query
                        ->withCount('curations')
                        ->with([
                            'users',
                            'users.roles',
                        ]);
                },
            ])
            ->findOrFail($id);

        return new WorkingGroupResource($group);
    }

    public function export($id)
    {
        $group = WorkingGroup::query()
            ->with([
                'expertPanels',
                'expertPanels.users',
            ])
            ->findOrFail($id);
        $expertPanelIds = $group->expertPanels->pluck('id')->all();

        $timestamp = now()->format('Ymd_His');
        $safeGroupName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $group->name ?? 'working_group');

        $exportDir = storage_path('app/exports');
        $tmpDir = storage_path('app/exports/wg_'.$id.'_'.$timestamp);

        File::ensureDirectoryExists($exportDir);
        File::ensureDirectoryExists($tmpDir);

        $expertPanelsCsv = $tmpDir.'/expert_panels.csv';
        $peopleCsv = $tmpDir.'/people.csv';
        $curationsCsv = $tmpDir.'/curations.csv';

        /*
        * People CSV
        */
        $handle = fopen($peopleCsv, 'w');

        fputcsv($handle, [
            'Expert Panel ID',
            'Expert Panel Name',
            'Name',
            'Email',
            'Active',
            'Is Coordinator',
            'Is Curator',
            'Deactivated At',
        ]);

        foreach ($group->expertPanels as $panel) {
            foreach ($panel->users as $user) {
                fputcsv($handle, [
                    $panel->id,
                    $panel->name,
                    $user->name,
                    $user->email,
                    $user->deactivated_at ? 'No' : 'Yes',
                    optional($user->pivot)->is_coordinator ? 'Yes' : 'No',
                    optional($user->pivot)->is_curator ? 'Yes' : 'No',
                    $user->deactivated_at,
                ]);
            }
        }

        fclose($handle);

        /*
        * Curations CSV
        */
        $handle = fopen($curationsCsv, 'w');

        fputcsv($handle, [
            'Precuration ID',
            'UUID',
            'GDM UUID',
            'Gene Symbol',
            'HGNC ID',
            'HGNC Name',
            'Disease Entity',
            'MONDO ID',
            'Mode of Inheritance',
            'Curation Type',
            'Expert Panel Name',
            'Curator Name',
            'Curator Email',
            'Current Status',
            'Archived',
            'Archived At',
            'Archive Reason',
            'Curation Date',
            'Updated At',
        ]);

        Curation::query()
            ->with([
                'expertPanel',
                'curator',
                'disease',
                'currentStatus',
                'modeOfInheritance',
                'curationType',
            ])
            ->whereIn('expert_panel_id', $expertPanelIds)
            ->orderBy('expert_panel_id')
            ->orderBy('gene_symbol')
            ->chunkById(500, function ($curations) use ($handle) {
                foreach ($curations as $curation) {
                    $diseaseEntity = null;

                    if ($curation->disease) {
                        $diseaseEntity = $curation->disease->name;
                    } elseif ($curation->disease_entity_notes) {
                        $diseaseEntity = $curation->disease_entity_notes;
                    }

                    fputcsv($handle, [
                        $curation->id,
                        $curation->uuid,
                        $curation->gdm_uuid,
                        $curation->gene_symbol,
                        $curation->hgnc_id,
                        $curation->hgnc_name,
                        $diseaseEntity,
                        $curation->mondo_id,
                        optional($curation->modeOfInheritance)->abbreviation,
                        optional($curation->curationType)->description,                        
                        optional($curation->expertPanel)->name,
                        optional($curation->curator)->name,
                        optional($curation->curator)->email,
                        optional($curation->currentStatus)->name,
                        $curation->is_archived ? 'Yes' : 'No',
                        $curation->archived_at,
                        $curation->archive_reason,
                        $curation->curation_date,
                        $curation->updated_at,
                    ]);
                }
            });

        fclose($handle);

        /*
        * ZIP everything together
        */
        $zipFilename = $safeGroupName.'_export_'.$timestamp.'.zip';
        $zipPath = $exportDir.'/'.$zipFilename;

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            File::deleteDirectory($tmpDir);

            abort(500, 'Could not create export file.');
        }

        $zip->addFile($peopleCsv, 'people.csv');
        $zip->addFile($curationsCsv, 'curations.csv');
        $zip->close();

        File::deleteDirectory($tmpDir);

        return response()
            ->download($zipPath, $zipFilename, [
                'Content-Type' => 'application/zip',
            ])
            ->deleteFileAfterSend(true);
    }
}
