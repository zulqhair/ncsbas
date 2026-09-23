<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ImportNcsbWorkbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assessment = $this->route('assessment');
        $user = $this->user();

        return $assessment instanceof Assessment
            && $assessment->status === 'draft'
            && ($assessment->user_id === $user?->id || $user?->isAdmin());
    }

    public function rules(): array
    {
        return [
            'workbook' => ['required', 'file', 'mimes:xlsx', 'extensions:xlsx', 'max:10240'],
            'replace_answers' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $assessment = $this->route('assessment');

                if ($assessment instanceof Assessment && $assessment->details()->doesntExist()) {
                    $validator->errors()->add(
                        'workbook',
                        'Add at least one assessment detail before importing a workbook.',
                    );
                }
            },
        ];
    }
}
