<?php

namespace HiEvents\Http\Actions\Accounts\Razorpay;

use HiEvents\DomainObjects\AccountDomainObject;
use HiEvents\DomainObjects\Enums\Role;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Resources\Account\Razorpay\RazorpayLinkedAccountsResponseResource;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\GetRazorpayLinkedAccountsHandler;
use Illuminate\Http\JsonResponse;
use Throwable;

class GetRazorpayLinkedAccountsAction extends BaseAction
{
    public function __construct(
        private readonly GetRazorpayLinkedAccountsHandler $getRazorpayLinkedAccountsHandler,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(int $accountId): JsonResponse
    {
        $this->isActionAuthorized($accountId, AccountDomainObject::class, Role::ADMIN);

        $result = $this->getRazorpayLinkedAccountsHandler->handle($accountId);

        return $this->resourceResponse(
            resource: RazorpayLinkedAccountsResponseResource::class,
            data: $result,
        );
    }
}
