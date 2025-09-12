<?php

namespace Tests\Feature;

use App\Exceptions\BonusGenerationException;
use App\Models\Bonus;
use App\Models\Client;
use App\Models\Sale;
use App\Models\User;
use App\Services\BonusGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BonusGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private BonusGenerator $bonusGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->bonusGenerator = new BonusGenerator();
    }

    /** @test */
    public function it_generates_a_1000_bonus_when_sales_meet_the_50k_threshold(): void
    {
        $this->actingAs($this->user);

        // Generar ventas que cumplen la primera regla.
        $sales = Sale::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'total_amount' => 20000,
            'paid_amount' => 10000, // 50%
        ]);

        $salesTotal = $sales->sum('total_amount'); // 60,000
        $paidTotal = $sales->sum('paid_amount'); // 30,000

        $this->assertGreaterThanOrEqual(50000, $salesTotal);
        $this->assertGreaterThanOrEqual($salesTotal * 0.4, $paidTotal);

        $bonus = $this->bonusGenerator->generate($this->user, $sales);

        $this->assertInstanceOf(Bonus::class, $bonus);
        $this->assertEquals(1000, $bonus->amount);
        $this->assertCount(1, $this->user->bonuses);
        $this->assertEquals($sales->pluck('id'), $bonus->sales->pluck('id'));
    }

    /** @test */
    public function it_generates_a_1500_bonus_when_sales_meet_the_75k_threshold(): void
    {
        $this->actingAs($this->user);

        // Generar ventas para el nivel de 75k.
        $sales = Sale::factory()->count(4)->create([
            'user_id' => $this->user->id,
            'total_amount' => 20000,
            'paid_amount' => 10000,
        ]);

        $bonus = $this->bonusGenerator->generate($this->user, $sales);

        $this->assertEquals(1500, $bonus->amount);
    }

    /** @test */
    public function it_generates_a_2000_bonus_when_sales_meet_the_100k_threshold(): void
    {
        $this->actingAs($this->user);

        // Generar ventas para el nivel de 100k.
        $sales = Sale::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'total_amount' => 25000,
            'paid_amount' => 15000,
        ]);

        $bonus = $this->bonusGenerator->generate($this->user, $sales);

        $this->assertEquals(2000, $bonus->amount);
    }

    /** @test */
    public function it_fails_if_total_amount_is_below_the_minimum_threshold(): void
    {
        $this->expectException(BonusGenerationException::class);
        $this->expectExceptionMessage('El monto total de ventas no es suficiente para un bono.');

        $this->actingAs($this->user);

        $sales = Sale::factory()->count(1)->create([
            'user_id' => $this->user->id,
            'total_amount' => 45000,
            'paid_amount' => 20000,
        ]);

        $this->bonusGenerator->generate($this->user, $sales);
    }

    /** @test */
    public function it_fails_if_paid_amount_is_less_than_40_percent(): void
    {
        $this->expectException(BonusGenerationException::class);
        $this->expectExceptionMessage('El porcentaje de pagos no alcanza el 40% del monto total.');

        $this->actingAs($this->user);

        $sales = Sale::factory()->count(1)->create([
            'user_id' => $this->user->id,
            'total_amount' => 60000,
            'paid_amount' => 20000, // 33.33%
        ]);

        $this->bonusGenerator->generate($this->user, $sales);
    }

    /** @test */
    public function it_marks_used_sales_as_canjeado(): void
    {
        $this->actingAs($this->user);

        $sales = Sale::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'total_amount' => 20000,
            'paid_amount' => 10000,
        ]);

        $this->bonusGenerator->generate($this->user, $sales);

        foreach ($sales as $sale) {
            $this->assertTrue($sale->fresh()->canjeado);
        }
    }

    /** @test */
    public function it_prevents_using_canjeado_sales(): void
    {
        $this->expectException(BonusGenerationException::class);
        $this->expectExceptionMessage('Una o más ventas ya han sido canjeadas.');

        $this->actingAs($this->user);

        $sales = Sale::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'total_amount' => 20000,
            'paid_amount' => 10000,
            'canjeado' => true,
        ]);

        $this->bonusGenerator->generate($this->user, $sales);
    }
}