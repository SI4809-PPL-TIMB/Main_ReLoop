@if ($errors->any())
    <div id="reset-error" role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        {{ $errors->first() }}
    </div>
@endif
