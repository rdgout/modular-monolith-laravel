<?php

declare(strict_types=1);

namespace Laracon\Order\Application\Http\Controllers;

use Laracon\Order\Application\Http\Requests\StoreOrderRequest;
use Laracon\Order\Application\Http\Requests\UpdateOrderRequest;
use Laracon\Order\Application\Http\Resources\Order as OrderResource;
use Laracon\Order\Domain\Models\Order;
use Laracon\Payment\Domain\Contracts\PaymentServiceInterface;
use Laracon\Product\Domain\Contracts\ProductRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  \Laracon\Product\Domain\Contracts\ProductRepositoryInterface  $productRepository
     * @param  \Laracon\Payment\Domain\Contracts\PaymentServiceInterface  $paymentService
     * @return void
     */
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private PaymentServiceInterface $paymentService
    ) {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders = Order::paginate(10);

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Laracon\Order\Application\Http\Requests\StoreOrderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrderRequest $request)
    {
        $orderId = null;
        $validated = $request->validated();
        $price = $this->productRepository->getPrice((int)$validated['product_id']);

        DB::transaction(function () use (&$orderId, $validated, $price) {
            // 1. decrement stock via product module
            $this->productRepository->decrementStock((int)$validated['product_id'], (int)$validated['quantity']);

            // 2. TODO: create order. Just setting dummy order id for now.
            $orderId = 1;

            // 3. make payment via payment module
            $amount = $price * (int)$validated['quantity'];
            $this->paymentService->pay($orderId, $amount, $validated['payment_method']);
        });

        return response()->json(['order_id' => $orderId], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Laracon\Order\Domain\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        return new OrderResource($order);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Laracon\Order\Application\Http\Requests\UpdateOrderRequest  $request
     * @param  \Laracon\Order\Domain\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        //
    }
}
