<x-filament::page>
<div class="space-y-6 max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">

    {{-- TABLA DE VENTAS --}}
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-2 text-gray-800">Paso 1: Selecciona las ventas para el bono</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th>
                            <input type="checkbox"
                                   class="rounded text-indigo-600 focus:ring-indigo-500"
                                   wire:click="toggleSelectAll"
                                   @checked(count($selectedSales) === $sales->count())>
                            <span class="ml-2 text-xs text-gray-500">Seleccionar todo</span>
                        </th>
                        <th>Cliente</th>
                        <th>Monto Total</th>
                        <th>Monto Pagado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}">
                            <td>
                                <input type="checkbox"
                                       value="{{ $sale->id }}"
                                       @checked(in_array($sale->id, $selectedSales))
                                       wire:click="toggleSale({{ $sale->id }})">
                            </td>
                            <td>{{ $sale->client->name }}</td>
                            <td>${{ number_format($sale->total_amount, 2) }}</td>
                            <td>${{ number_format($sale->paid_amount, 2) }}</td>
                            <td>{{ $sale->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- RESUMEN Y BONOS --}}
    @if (count($selectedSales) > 0)
        <div class="bg-white p-6 rounded-lg shadow-md">
            {{-- RESUMEN --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 border rounded-lg bg-gray-50">
                <div>Total Seleccionado: ${{ number_format($totalAmountSelected, 2) }}</div>
                <div>Pagado Seleccionado: ${{ number_format($paidAmountSelected, 2) }}</div>
                <div @class([
                    'text-xl font-semibold',
                    'text-green-600' => $paidPercentage >= 40,
                    'text-red-600' => $paidPercentage < 40,
                ])>
                    Porcentaje Pagado: {{ number_format($paidPercentage, 2) }}%
                </div>
            </div>

            {{-- BONOS DISPONIBLES --}}
            @if (!empty($availableBonuses))
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 justify-items-center">
                    @foreach ($availableBonuses as $bonus)
                        <div class="min-w-[250px] max-w-xs p-6 bg-green-50 border border-green-200 rounded-lg shadow-md text-center flex flex-col justify-between hover:shadow-lg transition-shadow duration-300">
                            <p class="text-2xl font-bold text-green-700">Bono ${{ number_format($bonus['amount']) }}</p>
                            <p class="mt-2">Requiere ventas >= ${{ number_format($bonus['min_total']) }}</p>
                            <button wire:click="selectBonusForConfirmation({{ $bonus['amount'] }}, {{ $bonus['min_total'] }})"
                                    class="mt-4 px-4 py-2 bg-green-600 text-black rounded-lg hover:bg-green-700 transition">
                                Seleccionar
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-gray-500">No cumples los requisitos para un bono.</div>
            @endif
        </div>
    @endif

    {{-- CONFIRMACIÓN DEL BONO --}}
    @if ($bonusForConfirmation)
        <div class="bg-white p-6 rounded-lg shadow-md flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-lg font-semibold">
                Confirmar bono de ${{ number_format($bonusForConfirmation['amount']) }} para {{ count($selectedSales) }} ventas.
            </p>
            <div class="flex gap-4">
                <button wire:click="generateBonus" class="px-6 py-3 bg-green-600 text-black rounded-lg hover:bg-green-700 transition">
                    Confirmar
                </button>
                <button wire:click="$set('bonusForConfirmation', null)" class="px-6 py-3 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition">
                    Cancelar
                </button>
            </div>
        </div>
    @endif

</div>

<script>
    window.addEventListener('hide-big-message', event => {
        setTimeout(() => {
            @this.showBigMessage = false;
        }, event.detail.timeout);
    });
</script>
</x-filament::page>
