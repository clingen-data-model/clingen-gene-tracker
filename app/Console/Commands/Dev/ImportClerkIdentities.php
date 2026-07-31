<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;

class ImportClerkIdentities extends Command
{
    protected $signature = 'users:import-clerk-identities
        {path : CSV file path exported from GPM}
        {--dry-run : Preview changes without saving}
        {--force : Allow overwriting existing different clingen_uuid/clerk_user_id values}';

    protected $description = 'Import Clerk identity mappings into GT users table.';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));

        if (!file_exists($path)) {
            $this->error("CSV file not found: {$path}");

            return self::FAILURE;
        }

        $userModel = config('auth.providers.users.model');

        if (!$userModel || !class_exists($userModel)) {
            $this->error('Could not resolve User model from config(auth.providers.users.model).');

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');

        if (!$handle) {
            $this->error("Could not open CSV file: {$path}");

            return self::FAILURE;
        }

        $header = fgetcsv($handle);

        if (!$header) {
            $this->error('CSV is empty.');

            return self::FAILURE;
        }

        $header = array_map(fn ($value) => trim((string) $value), $header);

        $requiredColumns = [
            'gt_user_id',
            'email',
            'clingen_uuid',
            'clerk_user_id',
        ];

        foreach ($requiredColumns as $column) {
            if (!in_array($column, $header, true)) {
                $this->error("Missing required CSV column: {$column}");

                return self::FAILURE;
            }
        }

        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $lineNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            $data = array_combine($header, $row);

            if (!$data) {
                $this->warn("Line {$lineNumber}: Could not read row.");
                $failed++;
                continue;
            }

            $result = $this->importRow($userModel, $data, $lineNumber);

            match ($result) {
                'updated' => $updated++,
                'skipped' => $skipped++,
                'failed' => $failed++,
                default => $failed++,
            };
        }

        fclose($handle);

        $this->newLine();
        $this->info($this->option('dry-run') ? 'Dry run complete.' : 'Import complete.');
        $this->line("Updated: {$updated}");
        $this->line("Skipped: {$skipped}");
        $this->line("Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function importRow(string $userModel, array $data, int $lineNumber): string
    {
        $gtUserId = trim((string) ($data['gt_user_id'] ?? ''));
        $email = $this->normalizeEmail($data['email'] ?? null);
        $clingenUuid = trim((string) ($data['clingen_uuid'] ?? ''));
        $clerkUserId = trim((string) ($data['clerk_user_id'] ?? ''));

        if (!$gtUserId || !$email || !$clingenUuid || !$clerkUserId) {
            $this->warn("Line {$lineNumber}: Missing required value.");
            return 'failed';
        }

        $user = $userModel::query()->find($gtUserId);

        if (!$user) {
            $this->warn("Line {$lineNumber}: GT user {$gtUserId} not found.");
            return 'failed';
        }

        $userEmail = $this->normalizeEmail($user->email);

        if ($userEmail !== $email) {
            $this->warn("Line {$lineNumber}: Email mismatch for user {$gtUserId}. CSV={$email}, GT={$userEmail}");
            return 'failed';
        }

        if ($this->valueUsedByAnotherUser($userModel, $user, 'clingen_uuid', $clingenUuid)) {
            $this->warn("Line {$lineNumber}: clingen_uuid already belongs to another GT user: {$clingenUuid}");
            return 'failed';
        }

        if ($this->valueUsedByAnotherUser($userModel, $user, 'clerk_user_id', $clerkUserId)) {
            $this->warn("Line {$lineNumber}: clerk_user_id already belongs to another GT user: {$clerkUserId}");
            return 'failed';
        }

        if (!$this->option('force')) {
            if ($user->clingen_uuid && $user->clingen_uuid !== $clingenUuid) {
                $this->warn("Line {$lineNumber}: User {$gtUserId} already has a different clingen_uuid.");
                return 'failed';
            }

            if ($user->clerk_user_id && $user->clerk_user_id !== $clerkUserId) {
                $this->warn("Line {$lineNumber}: User {$gtUserId} already has a different clerk_user_id.");
                return 'failed';
            }
        }

        if ($user->clingen_uuid === $clingenUuid && $user->clerk_user_id === $clerkUserId) {
            $this->line("Line {$lineNumber}: User {$gtUserId} already up to date.");
            return 'skipped';
        }

        $this->line("Line {$lineNumber}: Updating GT user {$gtUserId} ({$email})");

        if (!$this->option('dry-run')) {
            $user->forceFill([
                'clingen_uuid' => $clingenUuid,
                'clerk_user_id' => $clerkUserId,
            ])->save();
        }

        return 'updated';
    }

    protected function valueUsedByAnotherUser(string $userModel, $user, string $column, string $value): bool
    {
        $keyName = $user->getKeyName();
        return $userModel::query()->where($column, $value)->where($keyName, '!=', $user->getKey())->exists();
    }

    protected function normalizeEmail(?string $email): ?string
    {
        if (!$email) { return null; }
        $email = trim(mb_strtolower($email));
        return $email === '' ? null : $email;
    }
}