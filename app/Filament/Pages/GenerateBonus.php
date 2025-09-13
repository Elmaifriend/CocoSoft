<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Sale;
use App\Services\BonusGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Exceptions\BonusGenerationException;

class GenerateBonus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Generar Bonos';
    protected static ?string $navigationGroup = 'Bonos';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.generate-bonus';

    public array $selectedSales = [];
    public float $totalAmountSelected = 0;
    public float $paidAmountSelected = 0;
    public float $paidPercentage = 0;
    public array $availableBonuses = [];
    public ?array $bonusForConfirmation = null;
    public Collection $sales;
    public Collection $selectedSalesData;

    protected $bonusGenerator;

    public function mount(): void
    {
        $this->bonusGenerator = new BonusGenerator();
        $this->loadSales();
        $this->selectedSalesData = collect();
    }

    public function loadSales(): void
    {
        $this->sales = Sale::query()
            ->where('user_id', Auth::id())
            ->where('canjeado', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updatedSelectedSales(): void
    {
        $this->recalculateTotalsAndBonuses();
        $this->bonusForConfirmation = null;
    }

    public function toggleSale(int $saleId): void
    {
        if (in_array($saleId, $this->selectedSales)) {
            $this->selectedSales = array_diff($this->selectedSales, [$saleId]);
        } else {
            $this->selectedSales[] = $saleId;
        }

        $this->recalculateTotalsAndBonuses();
        $this->bonusForConfirmation = null;
    }

    public function recalculateTotalsAndBonuses(): void
    {
        if (empty($this->selectedSales)) {
            $this->resetCalculations();
            return;
        }

        $sales = Sale::whereIn('id', $this->selectedSales)->get();
        $this->selectedSalesData = $sales;

        $this->totalAmountSelected = $sales->sum('total_amount');
        $this->paidAmountSelected = $sales->sum('paid_amount');
        $this->paidPercentage = ($this->totalAmountSelected > 0)
            ? ($this->paidAmountSelected / $this->totalAmountSelected) * 100
            : 0;

        $this->availableBonuses = $this->determineAvailableBonuses($sales);
    }

    protected function determineAvailableBonuses(Collection $sales): array
    {
        if (!isset($this->bonusGenerator)) {
            $this->bonusGenerator = new BonusGenerator();
        }

        if ($this->paidPercentage < 40) {
            return [];
        }

        $total = $sales->sum('total_amount');
        $bonusLevels = $this->bonusGenerator->getBonusLevels();
        $qualifiedBonuses = [];

        foreach ($bonusLevels as $minTotal => $bonusAmount) {
            if ($total >= $minTotal) {
                $qualifiedBonuses[] = [
                    'amount' => $bonusAmount,
                    'min_total' => $minTotal,
                ];
            }
        }

        return collect($qualifiedBonuses)->sortByDesc('amount')->values()->all();
    }

    public function selectBonusForConfirmation(int $amount, int $minTotal): void
    {
        $this->bonusForConfirmation = [
            'amount' => $amount,
            'min_total' => $minTotal,
        ];
    }

    public function generateBonus(): void
    {
        if (empty($this->selectedSales)) {
            Notification::make()->title('Error')->body('No hay ventas seleccionadas.')->danger()->send();
            return;
        }

        try {
            $salesToProcess = Sale::whereIn('id', $this->selectedSales)->get();

            // Crear nueva instancia aquí
            $bonusGenerator = new BonusGenerator();

            $bonus = $bonusGenerator->generate(Auth::user(), $salesToProcess);

            Notification::make()
                ->title('Bono generado')
                ->body("Se generó un bono de $".number_format($bonus->amount, 2))
                ->success()
                ->send();

            // Recargar la página
            redirect()->route('filament.admin.resources.bonuses.index');

        } catch (BonusGenerationException $e) {
            Notification::make()
                ->title('Error al generar bono')
                ->body($e->getMessage())
                ->danger()
                ->send();
            $this->bonusForConfirmation = null;
        }
    }

    protected function resetCalculations(): void
    {
        $this->totalAmountSelected = 0;
        $this->paidAmountSelected = 0;
        $this->paidPercentage = 0;
        $this->availableBonuses = [];
        $this->bonusForConfirmation = null;
        $this->selectedSalesData = collect();
    }

    public function toggleSelectAll(): void
    {
        if (count($this->selectedSales) === $this->sales->count()) {
            $this->selectedSales = [];
        } else {
            $this->selectedSales = $this->sales->pluck('id')->toArray();
        }

        $this->recalculateTotalsAndBonuses();
    }
}
