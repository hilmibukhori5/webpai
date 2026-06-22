<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ajukan Penyetaraan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">

                    <div>
                        <span class="text-xs font-semibold text-slate-500">{{ $module->code }}</span>
                        <h3 class="text-lg font-semibold text-slate-900">{{ $module->name }}</h3>
                    </div>

                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 space-y-2 text-sm">
                        <p>
                            <span class="font-medium">Skema:</span>
                            {{ $result->decision === 'baru' ? 'PKS Baru (percentile)' : 'Adendum PKS Lama (rata-rata bobot SKS)' }}
                        </p>
                        <p>
                            <span class="font-medium">Biaya:</span>
                            Rp{{ number_format($result->price, 0, ',', '.') }}
                        </p>
                        <p class="text-slate-500">{{ $result->reason }}</p>
                    </div>

                    @if ($result->decidingComponents())
                        <div>
                            <p class="text-sm font-medium text-slate-700 mb-2">Rincian matkul komponen:</p>
                            <table class="w-full text-sm border border-slate-200 rounded-lg overflow-hidden">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="text-left p-2">Matkul</th>
                                        <th class="text-left p-2">SKS</th>
                                        <th class="text-left p-2">NA</th>
                                        <th class="text-left p-2">NH</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($result->decidingComponents() as $component)
                                        <tr class="border-t border-slate-100">
                                            <td class="p-2">{{ $component['course_code'] }} - {{ $component['course_name'] }}</td>
                                            <td class="p-2">{{ $component['sks'] }}</td>
                                            <td class="p-2">{{ $component['na'] }}</td>
                                            <td class="p-2">{{ $component['nh'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('submissions.store', $module->code) }}" class="space-y-4">
                        @csrf

                        <label class="flex items-start gap-2 text-sm">
                            <input type="checkbox" name="bersedia_diajukan" value="1" class="mt-1 rounded border-gray-300" required>
                            <span>Saya bersedia mengajukan penyetaraan modul ini sesuai skema & rincian nilai di atas.</span>
                        </label>
                        <x-input-error :messages="$errors->get('bersedia_diajukan')" class="mt-1" />

                        <label class="flex items-start gap-2 text-sm">
                            <input type="checkbox" name="bersedia_bayar" value="1" class="mt-1 rounded border-gray-300" required>
                            <span>Saya bersedia membayar biaya penyetaraan sebesar Rp{{ number_format($result->price, 0, ',', '.') }}.</span>
                        </label>
                        <x-input-error :messages="$errors->get('bersedia_bayar')" class="mt-1" />

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700">Batal</a>
                            <x-primary-button>Kirim Pengajuan</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
