<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import Nilai') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-4 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('importErrors'))
                <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm space-y-1">
                    <p class="font-medium">Sebagian baris dilewati karena tidak valid:</p>
                    <ul class="list-disc list-inside">
                        @foreach (session('importErrors') as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div>
                        <p class="text-sm text-gray-600">
                            Upload nilai per matkul per semester. Kolom file: <strong>No Induk, Nama, NA, NH</strong>.
                            Setelah import, <code>course_thresholds</code> untuk matkul ini otomatis di-recompute.
                        </p>
                        <a href="{{ asset('samples/course_grades_sample.csv') }}" class="text-indigo-600 hover:text-indigo-800 text-sm underline" download>
                            Download contoh file (CSV)
                        </a>
                    </div>

                    <form method="POST" action="{{ route('admin.grades.import.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="course_id" value="Matkul" />
                            <select id="course_id" name="course_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">-- pilih matkul --</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>
                                        {{ $course->code }} - {{ $course->name }} ({{ $course->sks }} sks)
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('course_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="semester" value="Label semester" />
                            <x-text-input id="semester" name="semester" type="text" class="mt-1 block w-full" placeholder='mis. "Genap 2223"' :value="old('semester')" required />
                            <x-input-error :messages="$errors->get('semester')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="file" value="File (xlsx/xls/csv)" />
                            <input id="file" name="file" type="file" accept=".xlsx,.xls,.csv" class="mt-1 block w-full text-sm" required>
                            <x-input-error :messages="$errors->get('file')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end">
                            <x-primary-button>Import</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
