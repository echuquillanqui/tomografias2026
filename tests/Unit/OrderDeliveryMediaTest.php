<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderController;
use App\Models\Agreement;
use App\Models\Order;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class OrderDeliveryMediaTest extends TestCase
{
    #[DataProvider('deliveryMediaProvider')]
    public function test_delivery_media_selection_is_preserved(array $input, string $agreement, array $expectedOptions, string $expectedMedia): void
    {
        $order = new Order();
        $order->setRelation('agreement', new Agreement(['nombre_institucion' => $agreement]));
        $method = new ReflectionMethod(OrderController::class, 'applyAutomaticDeliveryMedia');

        $result = $method->invoke(new OrderController(), $order, $input);

        $this->assertSame($expectedOptions, $result['delivery_media_options']);
        $this->assertSame($expectedMedia, $result['delivery_media']);
    }

    public static function deliveryMediaProvider(): array
    {
        return [
            'selected CD' => [['delivery_media_options' => ['CD']], 'CONVENIO', ['CD'], 'CD'],
            'selected LINK' => [['delivery_media_options' => ['LINK']], 'PARTICULAR', ['LINK'], 'LINK'],
            'selected both' => [['delivery_media_options' => ['CD', 'LINK']], 'PARTICULAR', ['CD', 'LINK'], 'AMBOS'],
            'automatic CD default' => [[], 'PARTICULAR', ['CD'], 'CD'],
            'automatic LINK default' => [[], 'SEGURO', ['LINK'], 'LINK'],
        ];
    }
}
