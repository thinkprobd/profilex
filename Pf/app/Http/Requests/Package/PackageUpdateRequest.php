<?php

namespace App\Http\Requests\Package;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PackageUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'price' => 'required',
            'term' => 'required',
            'trial_days' => $this->is_trial == "1" ? 'required' : '',
            'number_of_blogs' => Rule::requiredIf(function () {
                return in_array('Blog', $this->features);
            }),
            'number_of_blog_categories' => Rule::requiredIf(function () {
                return in_array('Blog', $this->features);
            }),
            'number_of_services' => Rule::requiredIf(function () {
                return in_array('Service', $this->features);
            }),
            'number_of_skills' => Rule::requiredIf(function () {
                return in_array('Skill', $this->features);
            }),
            'number_of_portfolios' => Rule::requiredIf(function () {
                return in_array('Portfolio', $this->features);
            }),
            'number_of_portfolio_categories' => Rule::requiredIf(function () {
                return in_array('Portfolio', $this->features);
            }),
            'number_of_languages' => 'required',
            'number_of_job_expriences' => Rule::requiredIf(function () {
                return in_array('Experience', $this->features);
            }),
            'number_of_education' => Rule::requiredIf(function () {
                return in_array('Experience', $this->features);
            }),
            'number_of_vcards' => Rule::requiredIf(function () {
                    return in_array('vCard', $this->features);
            }),
            'themes' => 'required'
        ];
    }

}
