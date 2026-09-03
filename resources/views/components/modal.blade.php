<dialog class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50" aria-labelledby="confirmation-title" data-confirm-modal>
    <form method="dialog" class="p-6">
        <h2 id="confirmation-title" class="text-lg font-semibold">Confirm action</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600" data-confirm-message></p>
        <div class="mt-6 flex justify-end gap-3">
            <x-button type="submit" variant="secondary" value="cancel" data-confirm-cancel>Cancel</x-button>
            <x-button type="submit" variant="danger" value="confirm" data-confirm-submit>Continue</x-button>
        </div>
    </form>
</dialog>
