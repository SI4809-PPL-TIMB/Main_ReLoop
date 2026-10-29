@props(['name', 'label', 'placeholder' => 'Minimal 8 karakter', 'hint' => null])
<div>
    <label for="{{ $name }}" class="text-sm font-semibold">{{ $label }}</label>
    <div class="mt-2 flex items-center rounded-xl border border-slate-200 pl-4 pr-1 focus-within:border-ink has-[[aria-invalid=true]]:border-red-500 has-[:user-invalid]:border-red-500">
        <input id="{{ $name }}" name="{{ $name }}" type="password" required minlength="8" maxlength="1024" autocomplete="new-password" placeholder="{{ $placeholder }}"
               @if ($errors->any()) aria-invalid="true" aria-describedby="reset-error" @endif
               class="min-w-0 flex-1 bg-transparent py-3 outline-none">
        <button type="button" data-password-toggle aria-controls="{{ $name }}" aria-label="Tampilkan kata sandi" aria-pressed="false" class="group grid size-9 shrink-0 place-items-center text-slate-400">
            <svg class="size-5 group-aria-pressed:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="hidden size-5 group-aria-pressed:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6C3.9 8.3 2 12 2 12s3.5 7 10 7a9.8 9.8 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
        </button>
    </div>
    @if ($hint)<span class="mt-2 block text-xs text-slate-500">{{ $hint }}</span>@endif
</div>
