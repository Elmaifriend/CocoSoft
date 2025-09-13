<?php

namespace App\Services;

use App\Models\Bonus;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BonusGenerator
{
    /**
     * Niveles de bonos: clave = monto mínimo, valor = monto del bono.
     */
    protected array $bonusLevels = [
        100000 => 2000,
        75000 => 1500,
        50000 => 1000,
    ];

    /**
     * Devuelve los niveles de bonos.
     */
    public function getBonusLevels(): array
    {
        return $this->bonusLevels;
    }

    /**
     * Genera un bono para un usuario usando solo las ventas necesarias.
     *
     * @param User $user
     * @param Collection|array $sales
     * @return Bonus|null
     */
    public function generate(User $user, Collection|array $sales): ?Bonus
    {
        $sales = $sales instanceof Collection ? $sales : collect($sales);

        if ($sales->isEmpty()) {
            return null;
        }

        DB::beginTransaction();

        try {
            // Re-obtener las ventas con bloqueo
            $saleIds = $sales->pluck('id')->toArray();
            $lockedSales = Sale::whereIn('id', $saleIds)
                               ->lockForUpdate()
                               ->get();

            // Filtrar ventas válidas del usuario y no canjeadas
            $validSales = $lockedSales->filter(fn($sale) => $sale->user_id === $user->id && !$sale->canjeado);

            // Validar porcentaje pagado mínimo del 40%
            $totalAmount = $validSales->sum('total_amount');
            $paidAmount = $validSales->sum('paid_amount');
            if ($totalAmount < min(array_keys($this->bonusLevels)) || ($paidAmount / $totalAmount) * 100 < 40) {
                return null;
            }

            // Determinar el bono y las ventas mínimas necesarias
            $result = $this->determineBonusAmount($validSales);
            if (!$result) {
                return null;
            }

            $bonusAmount = $result['amount'];
            $selectedSales = $result['sales'];

            // Crear bono y asociar ventas
            $bonus = Bonus::create([
                'user_id' => $user->id,
                'amount' => $bonusAmount,
            ]);

            $bonus->sales()->attach($selectedSales->pluck('id'));

            // Marcar solo las ventas usadas como canjeadas
            Sale::whereIn('id', $selectedSales->pluck('id'))->update(['canjeado' => true]);

            DB::commit();

            return $bonus;

        } catch (\Throwable $e) {
            DB::rollBack();
            return null;
        }
    }

    /**
     * Determina el monto del bono y las ventas mínimas necesarias.
     *
     * @param Collection $sales
     * @return array|null ['amount' => float, 'sales' => Collection]
     */
    protected function determineBonusAmount(Collection $sales): ?array
    {
        krsort($this->bonusLevels);

        // Ordenar ventas de mayor a menor
        $sales = $sales->sortByDesc('total_amount')->values();
        $totalSalesAmount = $sales->sum('total_amount');

        foreach ($this->bonusLevels as $minTotal => $bonus) {
            if ($totalSalesAmount >= $minTotal) {
                $accumulated = 0;
                $selectedSales = collect();

                foreach ($sales as $sale) {
                    $accumulated += $sale->total_amount;
                    $selectedSales->push($sale);
                    if ($accumulated >= $minTotal) {
                        break;
                    }
                }

                return [
                    'amount' => (float) $bonus,
                    'sales' => $selectedSales,
                ];
            }
        }

        return null;
    }
}
