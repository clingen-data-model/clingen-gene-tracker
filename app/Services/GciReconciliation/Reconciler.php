<?php
namespace App\Services\GciReconciliation;

use Illuminate\Support\Facades\DB;

/** Matching writes only immutable reconciliation findings, never curations. */
class Reconciler
{
    private const SOURCE_HAS_PRECURATION = 'HAS_PRECURATION_ID';
    private const SOURCE_NO_PRECURATION = 'NO_PRECURATION_ID';

    private const METHOD_PRECURATION_ID = 'PRECURATION_ID';
    private const METHOD_GDM_UUID = 'GDM_UUID';

    private const STATUS_CONFIRMED = 'CONFIRMED';
    private const STATUS_NOT_FOUND = 'NOT_FOUND_IN_GT';
    private const STATUS_AMBIGUOUS = 'AMBIGUOUS_GDM_UUID';
    private const STATUS_ALREADY_RECONCILED = 'ALREADY_RECONCILED';
    private const STATUS_CONFLICT = 'CONFLICT';

    private array $deleted = [];
    private array $sourceUuidCounts = [];

    public function reconcile(string $runUuid, ?string $label, string $hasPath, string $noPath): void
    {
        $reader = new PayloadReader();
        $has = $reader->read(file_get_contents($hasPath), 'HAS_PRECURATION_ID');
        $no = $reader->read(file_get_contents($noPath), 'NO_PRECURATION_ID');
        $this->sourceUuidCounts = array_count_values(array_map(fn ($item) => strtolower(trim($item['PK'])), array_merge($has['Items'], $no['Items'])));
        $all = $this->loadGtCurations();
        $this->deleted = array_values(array_filter($all, fn ($c) => $c['deleted_at'] !== null));
        $byId = $this->indexById($all);
        $byUuid = $this->indexByGdmUuid(array_values(array_filter($all, fn ($c) => $c['deleted_at'] === null)));
        $reserved = $seen = [];
        DB::transaction(function () use ($runUuid, $label, $hasPath, $noPath, $has, $no, $byId, $byUuid, &$reserved, &$seen) {
            Run::firstOrCreate(['uuid' => $runUuid], ['label' => $label, 'status' => 'COMPLETED', 'started_at' => now(), 'completed_at' => now()]);
            $this->processHasPrecurationFile($runUuid, $label, $hasPath, $has['Items'], $byId, $byUuid, $reserved, $seen);
            $this->processNoPrecurationFile($runUuid, $label, $noPath, $no['Items'], $byUuid, $reserved, $seen);
        });
    }

    private function processHasPrecurationFile(
        string $runUuid,
        ?string $runLabel,
        string $sourcePath,
        array $items,
        array $gtById,
        array $gtByGdmUuid,
        array &$reservedGtIds,
        array &$seenGciPks
    ): void {
        $sourceHash = hash_file('sha256', $sourcePath);

        foreach ($items as $index => $rawItem) {
            $gci = $this->normalizeGciItem($rawItem, true);
            $issues = [];
            $conflictingGtIds = [];

            $this->trackGciPk($gci['gdm_uuid'], $seenGciPks, $issues);

            $selectedGt = null;
            $matchMethod = null;
            $precurationIdMatch = null;
            $precurationIdExistsDeleted = false;

            // First choice: GCI precurationId = GT curations.id.
            if ($gci['precuration_id'] === null) {
                $issues[] = 'GCI_PRECURATION_ID_MISSING';
            } elseif (!isset($gtById[$gci['precuration_id']])) {
                $precurationIdMatch = false;
                $issues[] = 'PRECURATION_ID_NOT_FOUND_IN_GT';
            } else {
                $gtByPrecurationId = $gtById[$gci['precuration_id']];

                if ($gtByPrecurationId['deleted_at'] !== null) {
                    $precurationIdMatch = false;
                    $precurationIdExistsDeleted = true;
                    $issues[] = 'PRECURATION_ID_POINTS_TO_DELETED_GT';
                } elseif (isset($reservedGtIds[$gtByPrecurationId['id']])) {
                    $precurationIdMatch = false;
                    $conflictingGtIds[] = $gtByPrecurationId['id'];
                    $issues[] = 'PRECURATION_ID_GT_ALREADY_RESERVED';
                } else {
                    $selectedGt = $gtByPrecurationId;
                    $matchMethod = self::METHOD_PRECURATION_ID;
                    $precurationIdMatch = true;
                }
            }

            // If precurationId fails, try GCI PK = GT gdm_uuid.
            if (!$selectedGt) {
                $allGdmMatches = $gci['gdm_uuid']
                    ? ($gtByGdmUuid[$gci['gdm_uuid']] ?? [])
                    : [];

                if (count($allGdmMatches) > 1) {
                    $this->insertRow(
                        $runUuid,
                        $runLabel,
                        self::SOURCE_HAS_PRECURATION,
                        $sourcePath,
                        $sourceHash,
                        $index + 1,
                        $gci,
                        $rawItem,
                        null,
                        [
                            'match_method' => self::METHOD_GDM_UUID,
                            'match_status' => self::STATUS_AMBIGUOUS,
                            'precuration_id_match' => $precurationIdMatch,
                            'precuration_id_exists_deleted' => $precurationIdExistsDeleted,
                            'gdm_uuid_duplicate_in_gt' => true,
                            'has_identity_warning' => true,
                            'conflicting_gt_curation_ids' => array_column($allGdmMatches, 'id'),
                            'issue_codes' => array_merge($issues, ['GDM_UUID_MATCHES_MULTIPLE_GT_CURATIONS']),
                        ]
                    );

                    continue;
                }

                if (count($allGdmMatches) === 1) {
                    $gtByGdmUuidMatch = $allGdmMatches[0];

                    if (isset($reservedGtIds[$gtByGdmUuidMatch['id']])) {
                        $this->insertRow(
                            $runUuid,
                            $runLabel,
                            self::SOURCE_HAS_PRECURATION,
                            $sourcePath,
                            $sourceHash,
                            $index + 1,
                            $gci,
                            $rawItem,
                            null,
                            [
                                'match_method' => self::METHOD_GDM_UUID,
                                'match_status' => self::STATUS_CONFLICT,
                                'precuration_id_match' => $precurationIdMatch,
                                'precuration_id_exists_deleted' => $precurationIdExistsDeleted,
                                'has_identity_warning' => true,
                                'conflicting_gt_curation_ids' => [$gtByGdmUuidMatch['id']],
                                'issue_codes' => array_merge($issues, ['GDM_UUID_MATCHES_GT_ALREADY_RESERVED']),
                            ]
                        );

                        continue;
                    }

                    $selectedGt = $gtByGdmUuidMatch;
                    $matchMethod = self::METHOD_GDM_UUID;
                    $precurationIdMatch = false;
                    $issues[] = 'MATCHED_BY_GDM_UUID_AFTER_PRECURATION_ID_FAILED';
                } else {
                    $this->insertRow(
                        $runUuid,
                        $runLabel,
                        self::SOURCE_HAS_PRECURATION,
                        $sourcePath,
                        $sourceHash,
                        $index + 1,
                        $gci,
                        $rawItem,
                        null,
                        [
                            'match_status' => self::STATUS_NOT_FOUND,
                            'precuration_id_match' => $precurationIdMatch,
                            'precuration_id_exists_deleted' => $precurationIdExistsDeleted,
                            'has_identity_warning' => true,
                            'conflicting_gt_curation_ids' => $conflictingGtIds,
                            'issue_codes' => array_merge($issues, ['NO_MATCH_BY_PRECURATION_ID_OR_GDM_UUID']),
                        ]
                    );

                    continue;
                }
            }

            $comparison = $this->compareConfirmedMatch(
                $gci,
                $selectedGt,
                $matchMethod,
                self::SOURCE_HAS_PRECURATION,
                $precurationIdMatch,
                $precurationIdExistsDeleted,
                $gtByGdmUuid
            );

            $this->insertRow(
                $runUuid,
                $runLabel,
                self::SOURCE_HAS_PRECURATION,
                $sourcePath,
                $sourceHash,
                $index + 1,
                $gci,
                $rawItem,
                $selectedGt,
                array_merge($comparison, [
                    'precuration_id_match' => $precurationIdMatch,
                    'precuration_id_exists_deleted' => $precurationIdExistsDeleted,
                    'issue_codes' => array_values(array_unique(array_merge(
                        $issues,
                        $comparison['issue_codes']
                    ))),
                ])
            );

            $reservedGtIds[$selectedGt['id']] = true;
        }
    }

    private function processNoPrecurationFile(
        string $runUuid,
        ?string $runLabel,
        string $sourcePath,
        array $items,
        array $gtByGdmUuid,
        array &$reservedGtIds,
        array &$seenGciPks
    ): void {
        $sourceHash = hash_file('sha256', $sourcePath);

        foreach ($items as $index => $rawItem) {
            $gci = $this->normalizeGciItem($rawItem, false);
            $issues = [];

            $this->trackGciPk($gci['gdm_uuid'], $seenGciPks, $issues);

            $allGdmMatches = $gci['gdm_uuid']
                ? ($gtByGdmUuid[$gci['gdm_uuid']] ?? [])
                : [];

            if (count($allGdmMatches) > 1) {
                $this->insertRow(
                    $runUuid,
                    $runLabel,
                    self::SOURCE_NO_PRECURATION,
                    $sourcePath,
                    $sourceHash,
                    $index + 1,
                    $gci,
                    $rawItem,
                    null,
                    [
                        'match_method' => self::METHOD_GDM_UUID,
                        'match_status' => self::STATUS_AMBIGUOUS,
                        'gdm_uuid_duplicate_in_gt' => true,
                        'has_identity_warning' => true,
                        'conflicting_gt_curation_ids' => array_column($allGdmMatches, 'id'),
                        'issue_codes' => array_merge($issues, ['GDM_UUID_MATCHES_MULTIPLE_GT_CURATIONS']),
                    ]
                );

                continue;
            }

            if (count($allGdmMatches) === 1) {
                $selectedGt = $allGdmMatches[0];

                if (isset($reservedGtIds[$selectedGt['id']])) {
                    $this->insertRow(
                        $runUuid,
                        $runLabel,
                        self::SOURCE_NO_PRECURATION,
                        $sourcePath,
                        $sourceHash,
                        $index + 1,
                        $gci,
                        $rawItem,
                        null,
                        [
                            'match_method' => self::METHOD_GDM_UUID,
                            'match_status' => self::STATUS_ALREADY_RECONCILED,
                            'has_identity_warning' => true,
                            'conflicting_gt_curation_ids' => [$selectedGt['id']],
                            'issue_codes' => array_merge($issues, ['GDM_UUID_MATCHES_GT_RESERVED_BY_FIRST_PASS']),
                        ]
                    );

                    continue;
                }

                $comparison = $this->compareConfirmedMatch(
                    $gci,
                    $selectedGt,
                    self::METHOD_GDM_UUID,
                    self::SOURCE_NO_PRECURATION,
                    null,
                    false,
                    $gtByGdmUuid
                );

                $this->insertRow(
                    $runUuid,
                    $runLabel,
                    self::SOURCE_NO_PRECURATION,
                    $sourcePath,
                    $sourceHash,
                    $index + 1,
                    $gci,
                    $rawItem,
                    $selectedGt,
                    array_merge($comparison, [
                        'issue_codes' => array_values(array_unique(array_merge(
                            $issues,
                            $comparison['issue_codes']
                        ))),
                    ])
                );

                $reservedGtIds[$selectedGt['id']] = true;

                continue;
            }

            $this->insertRow(
                $runUuid,
                $runLabel,
                self::SOURCE_NO_PRECURATION,
                $sourcePath,
                $sourceHash,
                $index + 1,
                $gci,
                $rawItem,
                null,
                [
                    'match_status' => self::STATUS_NOT_FOUND,
                    'issue_codes' => array_merge($issues, ['NO_GDM_UUID_MATCH_IN_GT']),
                ]
            );
        }
    }

    private function compareConfirmedMatch(
        array $gci,
        array $gt,
        string $matchMethod,
        string $sourceType,
        ?bool $precurationIdMatch,
        bool $precurationIdExistsDeleted,
        array $gtByGdmUuid
    ): array {
        $gdmUuidMatch = $this->compareString($gci['gdm_uuid'], $gt['gdm_uuid']);
        $geneMatch = $this->compareString($gci['gene_symbol'], $gt['gene_symbol']);
        $affiliationMatch = $this->compareScalar($gci['affiliation_id'], $gt['affiliation_id']);

        $diseaseMatch = $gci['freetext_disease']
            ? null
            : $this->compareString($gci['mondo_id'], $gt['mondo_id']);

        $moiMatch = $this->compareString($gci['moi_hp_id'], $gt['moi_hp_id']);

        $issues = [];

        if ($gci['freetext_disease']) {
            $issues[] = 'GCI_FREETEXT_DISEASE';
        }

        if ($gdmUuidMatch === false) {
            $issues[] = 'GDM_UUID_MISMATCH';
        }

        if ($geneMatch === false) {
            $issues[] = 'GENE_MISMATCH';
        }

        if ($affiliationMatch === false) {
            $issues[] = 'AFFILIATION_MISMATCH';
        }

        if ($diseaseMatch === false) {
            $issues[] = 'DISEASE_MISMATCH';
        }

        if ($moiMatch === false) {
            $issues[] = 'MOI_MISMATCH';
        }

        if (!$gt['uuid']) {
            $issues[] = 'GT_UUID_MISSING';
        }

        $gdmMatchesForGciPk = $gci['gdm_uuid']
            ? ($gtByGdmUuid[$gci['gdm_uuid']] ?? [])
            : [];

        $gdmMatchesForGt = $gt['gdm_uuid']
            ? ($gtByGdmUuid[$gt['gdm_uuid']] ?? [])
            : [];

        $gdmUuidDuplicateInGt = count($gdmMatchesForGciPk) > 1
            || count($gdmMatchesForGt) > 1;

        if ($gdmUuidDuplicateInGt) {
            $issues[] = 'GDM_UUID_DUPLICATE_IN_GT';
        }

        $conflictingGtIds = [];

        foreach ($gdmMatchesForGciPk as $match) {
            if ($match['id'] !== $gt['id']) {
                $conflictingGtIds[] = $match['id'];
            }
        }

        $hasIdentityWarning = false;

        if (
            $sourceType === self::SOURCE_HAS_PRECURATION
            && $matchMethod === self::METHOD_GDM_UUID
            && $precurationIdMatch === false
        ) {
            $hasIdentityWarning = true;
            $issues[] = 'PRECURATION_ID_DID_NOT_IDENTIFY_SELECTED_GT';
        }

        if (
            $gdmUuidMatch === false
            || $geneMatch === false
            || $affiliationMatch === false
            || $gdmUuidDuplicateInGt
            || $precurationIdExistsDeleted
        ) {
            $hasIdentityWarning = true;
        }

        $needsGdmUuidUpdate = $gci['gdm_uuid'] !== null && $gdmUuidMatch === false;

        $needsDiseaseUpdate = !$gci['freetext_disease']
            && $gci['mondo_id'] !== null
            && $diseaseMatch === false;

        $needsMoiUpdate = $gci['moi_hp_id'] !== null && $moiMatch === false;

        $needsGtUpdate = $needsGdmUuidUpdate
            || $needsDiseaseUpdate
            || $needsMoiUpdate;

        $safeUpdateCandidate = $sourceType === self::SOURCE_HAS_PRECURATION
            && $matchMethod === self::METHOD_PRECURATION_ID
            && $precurationIdMatch === true
            && $gdmUuidMatch === true
            && $needsGtUpdate
            && !$hasIdentityWarning
            && !$gci['freetext_disease']
            && $geneMatch === true
            && $affiliationMatch === true;

        return [
            'match_method' => $matchMethod,
            'match_status' => self::STATUS_CONFIRMED,
            'is_confirmed_match' => true,
            'gdm_uuid_match' => $gdmUuidMatch,
            'gene_match' => $geneMatch,
            'affiliation_match' => $affiliationMatch,
            'disease_match' => $diseaseMatch,
            'moi_match' => $moiMatch,
            'all_compared_fields_match' => $this->allComparisonFlagsMatch([
                $gdmUuidMatch,
                $geneMatch,
                $affiliationMatch,
                $diseaseMatch,
                $moiMatch,
            ]),
            'gdm_uuid_duplicate_in_gt' => $gdmUuidDuplicateInGt,
            'has_identity_warning' => $hasIdentityWarning,
            'needs_gt_update' => $needsGtUpdate,
            'needs_gdm_uuid_update' => $needsGdmUuidUpdate,
            'needs_disease_update' => $needsDiseaseUpdate,
            'needs_moi_update' => $needsMoiUpdate,
            'safe_update_candidate' => $safeUpdateCandidate,
            'conflicting_gt_curation_ids' => array_values(array_unique($conflictingGtIds)),
            'issue_codes' => array_values(array_unique($issues)),
        ];
    }

    private function insertRow(
        string $runUuid,
        ?string $runLabel,
        string $sourceType,
        string $sourcePath,
        string $sourceHash,
        int $rowNumber,
        array $gci,
        array $rawItem,
        ?array $gt,
        array $result
    ): void {
        $defaults = [
            'match_method' => null,
            'match_status' => self::STATUS_CONFLICT,
            'is_confirmed_match' => false,
            'precuration_id_match' => null,
            'gdm_uuid_match' => null,
            'gene_match' => null,
            'affiliation_match' => null,
            'disease_match' => null,
            'moi_match' => null,
            'all_compared_fields_match' => null,
            'gdm_uuid_duplicate_in_gt' => false,
            'precuration_id_exists_deleted' => false,
            'has_identity_warning' => false,
            'needs_gt_update' => false,
            'needs_gdm_uuid_update' => false,
            'needs_disease_update' => false,
            'needs_moi_update' => false,
            'safe_update_candidate' => false,
            'conflicting_gt_curation_ids' => [],
            'issue_codes' => [],
        ];

        $result = array_merge($defaults, $result);
        $deletedIds = array_column(array_filter($this->deleted, fn ($c) =>
            $c['gdm_uuid'] === $gci['gdm_uuid'] || $c['id'] === $gci['precuration_id']
        ), 'id');
        if (in_array('GCI_PK_ALREADY_SEEN_IN_RUN', $result['issue_codes'], true)) {
            $result['has_identity_warning'] = true;
            $result['safe_update_candidate'] = false;
        }
        $now = now();

        DB::table('gci_gt_reconciliations')->insert([
            'deleted_gt_curation_ids' => json_encode($deletedIds),
            'gt_is_retired' => ($gt['curation_status_id'] ?? null) == config('curations.statuses.retired-assignment'),
            'run_uuid' => $runUuid,
            'run_label' => $runLabel,
            'source_type' => $sourceType,
            'source_filename' => basename($sourcePath),
            'source_hash' => $sourceHash,
            'source_row_number' => $rowNumber,

            'gci_precuration_id' => $gci['precuration_id'],
            'gci_gdm_uuid' => $gci['gdm_uuid'],
            'gci_gene_symbol' => $gci['gene_symbol'],
            'gci_affiliation_id' => $gci['affiliation_id'],
            'gci_disease_id' => $gci['disease_id'],
            'gci_mondo_id' => $gci['mondo_id'],
            'gci_moi_hp_id' => $gci['moi_hp_id'],
            'gci_freetext_disease' => $gci['freetext_disease'],
            'raw_payload' => json_encode($rawItem),

            'gt_curation_id' => $gt['id'] ?? null,
            'gt_uuid' => $gt['uuid'] ?? null,
            'gt_gdm_uuid' => $gt['gdm_uuid'] ?? null,
            'gt_gene_symbol' => $gt['gene_symbol'] ?? null,
            'gt_affiliation_id' => $gt['affiliation_id'] ?? null,
            'gt_expert_panel_id' => $gt['expert_panel_id'] ?? null,
            'gt_expert_panel_name' => $gt['expert_panel_name'] ?? null,
            'gt_mondo_id' => $gt['mondo_id'] ?? null,
            'gt_moi_hp_id' => $gt['moi_hp_id'] ?? null,
            'gt_curation_status_id' => $gt['curation_status_id'] ?? null,
            'gt_archived_at' => $gt['archived_at'] ?? null,
            'gt_deleted_at' => $gt['deleted_at'] ?? null,

            'match_method' => $result['match_method'],
            'match_status' => $result['match_status'],
            'is_confirmed_match' => $result['is_confirmed_match'],

            'precuration_id_match' => $result['precuration_id_match'],
            'gdm_uuid_match' => $result['gdm_uuid_match'],
            'gene_match' => $result['gene_match'],
            'affiliation_match' => $result['affiliation_match'],
            'disease_match' => $result['disease_match'],
            'moi_match' => $result['moi_match'],
            'all_compared_fields_match' => $result['all_compared_fields_match'],

            'gdm_uuid_duplicate_in_gt' => $result['gdm_uuid_duplicate_in_gt'],
            'precuration_id_exists_deleted' => $result['precuration_id_exists_deleted'],
            'has_identity_warning' => $result['has_identity_warning'],
            'needs_gt_update' => $result['needs_gt_update'],
            'needs_gdm_uuid_update' => $result['needs_gdm_uuid_update'],
            'needs_disease_update' => $result['needs_disease_update'],
            'needs_moi_update' => $result['needs_moi_update'],
            'safe_update_candidate' => $result['safe_update_candidate'],

            'conflicting_gt_curation_ids' => count($result['conflicting_gt_curation_ids'])
                ? json_encode(array_values(array_unique($result['conflicting_gt_curation_ids'])))
                : null,
            'issue_codes' => count($result['issue_codes'])
                ? json_encode(array_values(array_unique($result['issue_codes'])))
                : null,

            'review_status' => 'PENDING',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function loadGtCurations(): array
    {
        return DB::table('curations as c')
            ->leftJoin('expert_panels as ep', 'ep.id', '=', 'c.expert_panel_id')
            ->leftJoin('affiliations as affiliation', 'affiliation.id', '=', 'ep.affiliation_id')
            ->leftJoin(
                'affiliations as affiliation_parent',
                'affiliation_parent.id',
                '=',
                'affiliation.parent_id'
            )
            ->leftJoin('mode_of_inheritances as moi', 'moi.id', '=', 'c.moi_id')
            ->select([
                'c.id',
                'c.uuid',
                'c.gdm_uuid',
                'c.gene_symbol',
                'c.mondo_id',
                'c.curation_status_id',
                'c.expert_panel_id',
                'c.archived_at',
                'c.deleted_at',
                'ep.name as expert_panel_name',
                'affiliation_parent.clingen_id as affiliation_clingen_id',
                'moi.hp_id as moi_hp_id',
            ])
            ->orderBy('c.id')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'uuid' => $this->normalizeUuid($row->uuid),
                    'gdm_uuid' => $this->normalizeUuid($row->gdm_uuid),
                    'gene_symbol' => $this->normalizeGene($row->gene_symbol),
                    'affiliation_id' => $row->affiliation_clingen_id !== null
                        ? (int) $row->affiliation_clingen_id
                        : null,
                    'expert_panel_id' => $row->expert_panel_id !== null
                        ? (int) $row->expert_panel_id
                        : null,
                    'expert_panel_name' => $row->expert_panel_name,
                    'mondo_id' => $this->normalizeMondo($row->mondo_id),
                    'moi_hp_id' => $this->normalizeHpId($row->moi_hp_id),
                    'curation_status_id' => $row->curation_status_id !== null
                        ? (int) $row->curation_status_id
                        : null,
                    'archived_at' => $row->archived_at,
                    'deleted_at' => $row->deleted_at,
                ];
            })
            ->all();
    }

    private function indexById(array $curations): array
    {
        $index = [];

        foreach ($curations as $curation) {
            $index[$curation['id']] = $curation;
        }

        return $index;
    }

    private function indexByGdmUuid(array $curations): array
    {
        $index = [];

        foreach ($curations as $curation) {
            if (!$curation['gdm_uuid']) {
                continue;
            }

            $index[$curation['gdm_uuid']][] = $curation;
        }

        return $index;
    }

    private function trackGciPk(?string $gdmUuid, array &$seenGciPks, array &$issues): void
    {
        if (!$gdmUuid) {
            $issues[] = 'GCI_PK_MISSING';

            return;
        }

        if (($this->sourceUuidCounts[$gdmUuid] ?? 0) > 1 || isset($seenGciPks[$gdmUuid])) {
            $issues[] = 'GCI_PK_ALREADY_SEEN_IN_RUN';
        }

        $seenGciPks[$gdmUuid] = true;
    }

    private function normalizeGciItem(array $item, bool $expectPrecurationId): array
    {
        $diseaseId = $this->normalizeString($item['disease'] ?? null);

        return [
            'precuration_id' => $expectPrecurationId
                ? $this->normalizePrecurationId($item['precurationId'] ?? null)
                : null,
            'gdm_uuid' => $this->normalizeUuid($item['PK'] ?? null),
            'gene_symbol' => $this->normalizeGene($item['gene'] ?? null),
            'affiliation_id' => $this->normalizeAffiliation($item['affiliation'] ?? null),
            'disease_id' => $diseaseId,
            'mondo_id' => $this->normalizeMondo($item['disease'] ?? null),
            'moi_hp_id' => $this->extractHpId($item['modeInheritance'] ?? null),
            'freetext_disease' => $this->isFreeTextDisease($diseaseId),
        ];
    }

    private function compareString(?string $gciValue, ?string $gtValue): ?bool
    {
        if ($gciValue === null) {
            return null;
        }

        if ($gtValue === null) {
            return false;
        }

        return $gciValue === $gtValue;
    }

    private function compareScalar($gciValue, $gtValue): ?bool
    {
        if ($gciValue === null) {
            return null;
        }

        if ($gtValue === null) {
            return false;
        }

        return (string) $gciValue === (string) $gtValue;
    }

    private function allComparisonFlagsMatch(array $flags): ?bool
    {
        $hasUncomparableField = false;

        foreach ($flags as $flag) {
            if ($flag === false) {
                return false;
            }

            if ($flag === null) {
                $hasUncomparableField = true;
            }
        }

        return $hasUncomparableField ? null : true;
    }

    private function normalizePrecurationId($value): ?int
    {
        $value = trim((string) $value);

        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }

    private function normalizeUuid($value): ?string
    {
        $value = strtolower(trim((string) $value));

        return $value !== '' ? $value : null;
    }

    private function normalizeGene($value): ?string
    {
        $value = strtoupper(trim((string) $value));

        return $value !== '' ? $value : null;
    }

    private function normalizeAffiliation($value): ?int
    {
        $value = trim((string) $value);

        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }

    private function normalizeString($value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function normalizeMondo($value): ?string
    {
        $value = strtoupper(trim((string) $value));

        if ($value === '') {
            return null;
        }

        if (preg_match('/^MONDO[_:](\d+)$/', $value, $matches)) {
            return 'MONDO:'.$matches[1];
        }

        return null;
    }

    private function extractHpId($value): ?string
    {
        if (!preg_match('/HP:\d+/i', (string) $value, $matches)) {
            return null;
        }

        return strtoupper($matches[0]);
    }

    private function normalizeHpId($value): ?string
    {
        $value = strtoupper(trim((string) $value));

        return $value !== '' ? $value : null;
    }

    private function isFreeTextDisease(?string $value): bool
    {
        return strpos(strtoupper((string) $value), 'FREETEXT_') === 0;
    }
}
