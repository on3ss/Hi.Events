<?php

namespace HiEvents\Http\Actions\Accounts\Razorpay;

use HiEvents\DomainObjects\AccountDomainObject;
use HiEvents\DomainObjects\Enums\Role;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Resources\Account\Razorpay\RazorpayLinkedAccountsResponseResource;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\GetRazorpayLinkedAccountHandler;
use Illuminate\Http\JsonResponse;
use Throwable;

class GetRazorpayLinkedAccountsAction extends BaseAction
{
    public function __construct(
        private readonly GetRazorpayLinkedAccountHandler $handler,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(int $accountId): JsonResponse
    {
        $this->isActionAuthorized($accountId, AccountDomainObject::class, Role::ADMIN);
        $result = $this->handler->handle($accountId);

        return $this->resourceResponse(
            resource: RazorpayLinkedAccountsResponseResource::class,
            data: $result,
        );
    }

    
}