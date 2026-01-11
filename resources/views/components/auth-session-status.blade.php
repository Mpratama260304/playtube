@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg font-medium text-sm text-green-700 dark:text-green-400']) }}>
        {{ $status }}
    </div>
@endif
