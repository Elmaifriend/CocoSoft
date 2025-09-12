<x-filament::page>
    <div class="space-y-6">
        <x-filament::card>
            <h2 class="text-xl font-bold mb-4">Ventas Elegibles para Bono</h2>
            <p class="text-gray-600">
                A continuación se muestra una lista de todas tus ventas no canjeadas. Si el total combinado cumple con los requisitos, se mostrarán las opciones de bono disponibles.
            </p>
            {{ $this->table }}
        </x-filament::card>

        @if (!empty($bonusOptions))
            <x-filament::card>
                <h2 class="text-xl font-bold mb-4">Opciones de Bono Disponibles</h2>

                <div class="grid md:grid-cols-3 gap-6">
                    @foreach ($bonusOptions as $bonus)
                        <div class="p-6 bg-green-50 border border-green-200 rounded-lg shadow-sm flex flex-col items-center text-center">
                            <x-heroicon-o-gift class="w-12 h-12 text-green-500 mb-2" />
                            <p class="text-2xl font-bold text-green-700">Bono ${{ number_format($bonus['monto']) }}</p>
                            <p class="text-gray-600 mt-1">Nivel: >= ${{ number_format($bonus['monto_minimo_ventas']) }}</p>
                            <p class="text-sm font-medium text-gray-500 mt-2">
                                <span class="block">Monto total de ventas: ${{ number_format($totalAmount, 2) }}</span>
                                <span class="block">Monto pagado: ${{ number_format($paidAmount, 2) }}</span>
                            </p>
                            <x-filament::button
                                class="mt-4 w-full"
                                wire:click="generateBonus({{ $bonus['monto'] }})"
                            >
                                Canjear este Bono
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            </x-filament::card>
        @else
            <x-filament::card>
                <div class="col-span-3 text-center p-8 bg-gray-50 rounded-lg">
                    <p class="text-lg font-medium text-gray-700">No hay opciones de bono disponibles con tus ventas elegibles.</p>
                    <p class="text-sm text-gray-500 mt-2">Asegúrate de que el total de tus ventas no canjeadas sea superior a $50,000 y que al menos el 40% haya sido pagado.</p>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament::page>
