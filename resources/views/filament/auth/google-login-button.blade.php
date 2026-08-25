<div class="mb-6 flex flex-col gap-4">
    <a
        href="{{ route('auth.google.redirect') }}"
        class="fi-btn fi-btn-size-lg flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10"
    >
        <svg class="h-5 w-5" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.43 3.58v3h3.93c2.3-2.12 3.63-5.24 3.63-8.82z" />
            <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.93-3c-1.09.73-2.48 1.16-4 1.16-3.08 0-5.68-2.08-6.61-4.87H1.35v3.09C3.32 21.3 7.34 24 12 24z" />
            <path fill="#FBBC05" d="M5.39 14.38c-.24-.73-.38-1.5-.38-2.38s.14-1.65.38-2.38V6.53H1.35A11.96 11.96 0 000 12c0 1.93.46 3.76 1.35 5.47l4.04-3.09z" />
            <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.45-3.45C17.94 1.19 15.24 0 12 0 7.34 0 3.32 2.7 1.35 6.53l4.04 3.09C6.32 6.83 8.92 4.75 12 4.75z" />
        </svg>
        <span>Continuar con Google</span>
    </a>

    <div class="flex items-center gap-3">
        <span class="h-px flex-1 bg-gray-200 dark:bg-white/10"></span>
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">o</span>
        <span class="h-px flex-1 bg-gray-200 dark:bg-white/10"></span>
    </div>
</div>
