<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MondoIdsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $input = $this->input('mondo_ids', $this->input('mondo_id', ''));

        $raw = is_array($input)
            ? $input
            : preg_split('/[,\s\n]+/', (string) $input, -1, PREG_SPLIT_NO_EMPTY);

        $normalized = collect($raw)
            ->map(function ($v) {
                $v = strtoupper(trim((string) $v));
                if (preg_match('/^\d+$/', $v)) {
                    return 'MONDO:' . str_pad($v, 7, '0', STR_PAD_LEFT);
                }
                if (preg_match('/^MONDO:\s*(\d{1,7})$/', $v, $m)) {
                    return 'MONDO:' . str_pad($m[1], 7, '0', STR_PAD_LEFT);
                }
                return $v; // leave as-is; rules will catch invalids
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->merge(['mondo_ids' => $normalized]);
    }

    public function rules(): array
    {
        return [
            'mondo_ids'   => ['required','array','min:1'],
            'mondo_ids.*' => ['regex:/^MONDO:\d{7}$/'], // enforce canonical format
        ];
    }

    public function messages(): array
    {
        return [
            'mondo_ids.*.regex' => 'Each MONDO ID must be in the form MONDO:0000000.',
        ];
    }
}
