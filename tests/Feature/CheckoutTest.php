<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PlatformMode;
use App\Facades\Platform;
use App\Mail\AccessGrantedMail;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Services\Payment\OrderFulfiller;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Platform::setMode(PlatformMode::Commercial));

it('crée une commande en attente pour une formation payante', function (): void {
    $course = Course::factory()->published()->create(['price_fcfa' => 50000]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('checkout.store', $course))
        ->assertRedirect();

    $order = Order::where('user_id', $user->getKey())->first();
    expect($order)->not->toBeNull()
        ->status->toBe(OrderStatus::Pending)
        ->amount_fcfa->toBe(50000)
        ->and($user->fresh()->hasAccessToCourse($course))->toBeFalse();
});

it('donne accès immédiat à une formation gratuite', function (): void {
    $course = Course::factory()->published()->create(['price_fcfa' => 0]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('checkout.store', $course))
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasAccessToCourse($course))->toBeTrue();
});

it('applique un code promo valide', function (): void {
    $course = Course::factory()->published()->create(['price_fcfa' => 100000]);
    $user = User::factory()->create();
    Coupon::create(['code' => 'PROMO20', 'discount_pct' => 20, 'max_uses' => 5, 'used_count' => 0, 'is_active' => true]);

    $this->actingAs($user)->post(route('checkout.store', $course), ['coupon' => 'promo20']);

    expect(Order::where('user_id', $user->getKey())->first()->amount_fcfa)->toBe(80000);
});

it('rejette un code promo expiré', function (): void {
    $course = Course::factory()->published()->create(['price_fcfa' => 100000]);
    $user = User::factory()->create();
    Coupon::create(['code' => 'OLD', 'discount_pct' => 50, 'max_uses' => 5, 'used_count' => 0, 'is_active' => true, 'expires_at' => now()->subDay()]);

    $this->actingAs($user)
        ->post(route('checkout.store', $course), ['coupon' => 'OLD'])
        ->assertSessionHasErrors('coupon');

    expect(Order::count())->toBe(0);
});

it('confirme le paiement : accès accordé, commande payée, e-mail envoyé, coupon consommé', function (): void {
    Mail::fake();
    $course = Course::factory()->published()->create(['price_fcfa' => 80000]);
    $user = User::factory()->create();
    $coupon = Coupon::create(['code' => 'X', 'discount_pct' => 10, 'max_uses' => 5, 'used_count' => 0, 'is_active' => true]);
    $order = Order::create([
        'user_id' => $user->getKey(), 'course_id' => $course->getKey(), 'coupon_id' => $coupon->getKey(),
        'amount_fcfa' => 72000, 'provider' => 'offline', 'provider_ref' => 'ABC123', 'status' => OrderStatus::Pending,
    ]);

    app(OrderFulfiller::class)->markPaid($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($user->fresh()->hasAccessToCourse($course))->toBeTrue()
        ->and($coupon->fresh()->used_count)->toBe(1);
    Mail::assertSent(AccessGrantedMail::class);
});

it('est idempotent : reconfirmer ne double pas l’inscription', function (): void {
    Mail::fake();
    $course = Course::factory()->published()->create(['price_fcfa' => 50000]);
    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->getKey(), 'course_id' => $course->getKey(),
        'amount_fcfa' => 50000, 'provider' => 'offline', 'provider_ref' => 'REF1', 'status' => OrderStatus::Pending,
    ]);

    app(OrderFulfiller::class)->markPaid($order);
    app(OrderFulfiller::class)->markPaid($order->fresh());

    expect($user->enrollments()->where('course_id', $course->getKey())->count())->toBe(1);
});

it('ferme l’achat en mode entreprise (capacité panier absente)', function (): void {
    Platform::setMode(PlatformMode::Enterprise);
    $course = Course::factory()->published()->create(['price_fcfa' => 50000]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('checkout.store', $course))
        ->assertNotFound();
});

it('réserve la gestion des commandes à l’admin en mode commercial', function (): void {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get('/admin/orders')->assertOk();

    Platform::setMode(PlatformMode::Enterprise);
    $this->actingAs($admin)->get('/admin/orders')->assertForbidden();
});
