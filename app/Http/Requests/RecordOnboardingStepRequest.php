<?php

namespace App\Http\Requests;

use App\Enums\OnboardingExperience;
use App\Enums\OnboardingStep;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordOnboardingStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $experience = $this->route('experience');
        assert($experience instanceof OnboardingExperience);

        return [
            'step' => ['required', 'string', Rule::enum(OnboardingStep::class)->only($experience->stepsCompletedByVisit())],
        ];
    }

    public function step(): OnboardingStep
    {
        return OnboardingStep::from($this->string('step')->toString());
    }
}
