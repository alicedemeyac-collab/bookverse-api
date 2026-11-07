<?php

namespace App\Http\Requests;

use App\Queries\BookSearchQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // le catalogue est public
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('language'))) {
            $this->merge(['language' => strtolower($this->input('language'))]);
        }
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
            'genre' => ['nullable', 'string', 'max:80'],
            'subgenre' => ['nullable', 'string', 'max:80'],
            'author' => ['nullable', 'string', 'min:2', 'max:100'],
            'publisher' => ['nullable', 'string', 'min:2', 'max:100'],
            'language' => ['nullable', 'alpha', 'size:2'],
            'year_min' => ['nullable', 'integer', 'between:1000,2100'],
            'year_max' => [
                'nullable', 'integer', 'between:1000,2100',
                Rule::when($this->filled('year_min'), ['gte:year_min']),
            ],
            'pages_min' => ['nullable', 'integer', 'between:1,10000'],
            'pages_max' => [
                'nullable', 'integer', 'between:1,10000',
                Rule::when($this->filled('pages_min'), ['gte:pages_min']),
            ],
            'sort' => ['nullable', Rule::in(array_keys(BookSearchQuery::SORTS))],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ];
    }
}
