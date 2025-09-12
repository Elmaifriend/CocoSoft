<?php

namespace App\Services;

use App\Models\Bonus;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Exceptions\BonusGenerationException;

class BonusGenerator
{
    /**
     * Define los niveles de bonos.
     * La clave es el monto mínimo de ventas y el valor es el monto del bono.
     */
    protected array $bonusLevels = [
        100000 => 2000,
        75000 => 1500,
        50000 => 1000,
    ];

    /**
     * Genera un bono para un usuario basado en las ventas seleccionadas.
     *
     * @param User $user
     * @param Collection|array $sales
     * @return Bonus
     * @throws BonusGenerationException
     */
    public function generate(User $user, Collection|array $sales): Bonus
    {
        $sales = $sales instanceof Collection ? $sales : collect($sales);

        // Bloqueo pesimista para evitar condiciones de carrera.
        DB::beginTransaction();

        try {
            // Re-obtener las ventas con bloqueo para la transacción.
            $saleIds = $sales->pluck('id')->toArray();
            $lockedSales = Sale::whereIn('id', $saleIds)
                               ->lockForUpdate()
                               ->get();

            // Validación de las reglas de negocio.
            $this->validateRules($user, $lockedSales);

            // Determina el monto del bono.
            $bonusAmount = $this->determineBonusAmount($lockedSales);
            if (!$bonusAmount) {
                throw new BonusGenerationException('No se alcanzó el monto mínimo para generar un bono.');
            }

            // Crear el bono y asociar las ventas.
            $bonus = Bonus::create([
                'user_id' => $user->id,
                'amount' => $bonusAmount,
            ]);

            $bonus->sales()->attach($lockedSales->pluck('id'));

            // Marcar las ventas como canjeadas.
            Sale::whereIn('id', $saleIds)->update(['canjeado' => true]);

            DB::commit();

            return $bonus;

        } catch (\Throwable $e) {
            DB::rollBack();
            throw new BonusGenerationException('Error al generar el bono: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Valida las reglas de negocio para la generación del bono.
     *
     * @param User $user
     * @param Collection $sales
     * @throws BonusGenerationException
     */
    protected function validateRules(User $user, Collection $sales): void
    {
        // Validación de que todas las ventas pertenecen al usuario y no están canjeadas.
        if ($sales->some(fn($sale) => $sale->user_id !== $user->id)) {
            throw new BonusGenerationException('Una o más ventas no pertenecen al usuario.');
        }

        if ($sales->some(fn($sale) => $sale->canjeado)) {
            throw new BonusGenerationException('Una o más ventas ya han sido canjeadas.');
        }

        $totalAmount = $sales->sum('total_amount');
        $paidAmount = $sales->sum('paid_amount');

        if ($totalAmount < min(array_keys($this->bonusLevels))) {
            throw new BonusGenerationException('El monto total de ventas no es suficiente para un bono.');
        }

        if (($paidAmount / $totalAmount) * 100 < 40) {
            throw new BonusGenerationException('El porcentaje de pagos no alcanza el 40% del monto total.');
        }
    }

    /**
     * Determina el monto del bono basado en el total de ventas.
     *
     * @param Collection $sales
     * @return float|null
     */
    protected function determineBonusAmount(Collection $sales): ?float
    {
        $totalAmount = $sales->sum('total_amount');

        // Ordenar los niveles de bono de mayor a menor.
        krsort($this->bonusLevels);

        foreach ($this->bonusLevels as $minTotal => $bonus) {
            if ($totalAmount >= $minTotal) {
                return (float) $bonus;
            }
        }

        return null;
    }
}