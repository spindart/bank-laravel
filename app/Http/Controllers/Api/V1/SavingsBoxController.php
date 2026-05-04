<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SavingsBoxDepositRequest;
use App\Http\Requests\SavingsBoxWithdrawRequest;
use App\Http\Requests\StoreSavingsBoxRequest;
use App\Http\Requests\UpdateSavingsBoxRequest;
use App\Services\SavingsBoxService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavingsBoxController extends Controller
{
    public function __construct(
        private readonly SavingsBoxService $savingsBoxService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'summary' => $this->savingsBoxService->summaryForUser($request->user()),
            'items' => $this->savingsBoxService->listForUser($request->user()),
        ], trans('messages.savings_box.index.success'));
    }

    public function store(StoreSavingsBoxRequest $request): JsonResponse
    {
        $savingsBox = $this->savingsBoxService->create($request->user(), $request->validated());

        return ApiResponse::success($savingsBox, trans('messages.savings_box.store.success'), 201);
    }

    public function show(Request $request, int $savingsBox): JsonResponse
    {
        $box = $this->savingsBoxService
            ->findForUser($request->user(), $savingsBox)
            ->load(['movements' => fn ($query) => $query->latest()->limit(50)]);

        return ApiResponse::success($box, trans('messages.savings_box.show.success'));
    }

    public function update(UpdateSavingsBoxRequest $request, int $savingsBox): JsonResponse
    {
        $box = $this->savingsBoxService->update($request->user(), $savingsBox, $request->validated());

        return ApiResponse::success($box, trans('messages.savings_box.update.success'));
    }

    public function destroy(Request $request, int $savingsBox): JsonResponse
    {
        $box = $this->savingsBoxService->cancel($request->user(), $savingsBox);

        return ApiResponse::success($box, trans('messages.savings_box.cancel.success'));
    }

    public function deposit(SavingsBoxDepositRequest $request, int $savingsBox): JsonResponse
    {
        $movement = $this->savingsBoxService->deposit(
            $request->user(),
            $savingsBox,
            (string) $request->validated('amount')
        );

        return ApiResponse::success($movement, trans('messages.savings_box.deposit.success'));
    }

    public function withdraw(SavingsBoxWithdrawRequest $request, int $savingsBox): JsonResponse
    {
        $movement = $this->savingsBoxService->withdraw(
            $request->user(),
            $savingsBox,
            (string) $request->validated('amount')
        );

        return ApiResponse::success($movement, trans('messages.savings_box.withdraw.success'));
    }

    public function movements(Request $request, int $savingsBox): JsonResponse
    {
        $box = $this->savingsBoxService->findForUser($request->user(), $savingsBox);

        return ApiResponse::success(
            $box->movements()->latest()->limit(100)->get(),
            trans('messages.savings_box.movements.success')
        );
    }
}
