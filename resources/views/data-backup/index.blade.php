<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Data Backup</h2>
            <p class="text-sm text-gray-500">Securely save or restore beneficiary records.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-blue-200 bg-blue-50 p-5 text-sm text-blue-900">
                <h3 class="font-semibold">Protect your records</h3>
                <p class="mt-1">Backups include active, rejected, completed, and archived beneficiary records, including rejection remarks and status history. Store the downloaded file securely.</p>
            </section>

            <div class="grid gap-6 md:grid-cols-2">
                <section class="rounded-lg bg-white p-6 shadow">
                    <h3 class="text-lg font-semibold text-gray-900">Save a backup</h3>
                    <p class="mt-2 text-sm text-gray-600">Download a complete Excel copy of the beneficiary records for secure storage or archiving.</p>
                    <a href="{{ route('data-backup.export') }}" class="mt-5 inline-flex rounded bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Download Excel Backup</a>
                </section>

                <section class="rounded-lg bg-white p-6 shadow">
                    <h3 class="text-lg font-semibold text-gray-900">Import beneficiary data</h3>
                    <p class="mt-2 text-sm text-gray-600">Upload an Excel or CSV file, review every row, and import valid records as pending applications.</p>
                    <a href="{{ route('beneficiaries.import.create') }}" class="mt-5 inline-flex rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Review Import File</a>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
