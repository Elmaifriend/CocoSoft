<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Services\BonusGenerator;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class GenerateBonus extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static string $view = 'filament.pages.generate-bonus';
    protected static ?string $navigationLabel = 'Generar Bonos';
    protected static ?string $title = 'Generar Bonos';
    protected static ?string $navigationGroup = 'Bonos';


    public ?array $bonusOptions = null;
    public float $totalAmount = 0;
    public float $paidAmount = 0;
    public Collection $eligibleSales;

    public function mount(): void
    {
        $this->eligibleSales = $this->getTableQuery()->get();
        $this->previewBonus();
    }

    protected function getTableQuery(): Builder
    {
        return Sale::query()
            ->where('user_id', auth()->id())
            ->where('canjeado', false)
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('client.name')->label('Cliente')->searchable(),
            TextColumn::make('total_amount')->label('Monto total')->money('USD'),
            TextColumn::make('paid_amount')->label('Monto pagado')->money('USD'),
        ];
    }

    public function previewBonus(): void
    {
        $sales = $this->eligibleSales;

        if ($sales->isEmpty()) {
            $this->bonusOptions = [];
            return;
        }

        $this->totalAmount = $sales->sum('total_amount');
        $this->paidAmount = $sales->sum('paid_amount');

        $levels = [100000 => 2000, 75000 => 1500, 50000 => 1000];

        $this->bonusOptions = [];
        foreach ($levels as $minTotal => $bonus) {
            if ($this->totalAmount >= $minTotal && ($this->paidAmount / $this->totalAmount) * 100 >= 40) {
                $this->bonusOptions[$bonus] = [
                    'monto' => $bonus,
                    'monto_minimo_ventas' => $minTotal,
                    'pagos_cumplen' => true
                ];
            }
        }
    }

    public function generateBonus(float $bonusAmount, BonusGenerator $generator): void
    {
        try {
            $bonus = $generator->generate(Auth::user(), $this->eligibleSales);

            Notification::make()
                ->title('¡Bono generado con éxito!')
                ->body("Se ha generado un bono de {$bonus->amount} USD.")
                ->success()
                ->send();

            $this->eligibleSales = $this->getTableQuery()->get();
            $this->previewBonus();

        } catch (\App\Exceptions\BonusGenerationException $e) {
            Notification::make()
                ->title('Error al generar bono')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
