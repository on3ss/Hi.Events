<?php

namespace HiEvents\Http\Actions\Accounts\Razorpay;

use HiEvents\DomainObjects\AccountDomainObject;
use HiEvents\DomainObjects\Enums\Role;

use HiEvents\Exceptions\CreateRazorpayLinkedAccountFailedException;

use HiEvents\Exceptions\SaasModeEnabledException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Resources\Account\Razorpay\RazorpayLinkedAccountResponseResource;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\CreateRazorpayLinkedAccountHandler;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\CreateRazorpayLinkedAccountDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CreateRazorpayLinkedAccountAction extends BaseAction
{
    public function __construct(
        private readonly CreateRazorpayLinkedAccountHandler $createRazorpayLinkedAccountHandler,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(int $accountId, Request $request): JsonResponse
    {
        $this->isActionAuthorized($accountId, AccountDomainObject::class, Role::ADMIN);

        try {
            $accountResult = $this->createRazorpayLinkedAccountHandler->handle(CreateRazorpayLinkedAccountDTO::from([
                'accountId' => $this->getAuthenticatedAccountId(),
            ]));
        } catch (CreateRazorpayLinkedAccountFailedException $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (SaasModeEnabledException $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: Response::HTTP_FORBIDDEN
            );
        }

        return $this->resourceResponse(
            resource: RazorpayLinkedAccountResponseResource::class,
            data: $accountResult
        );
    }
}
