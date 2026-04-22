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
            $requestData = $request->validate([
                'phone' => 'required|string',
                'contact_name' => 'required|string',
                'company_name' => 'required|string',
                'business_type' => 'required|string',
                'category' => 'required|string',
                'subcategory' => 'required|string',
                'pan' => 'required|string',
                'gst' => 'nullable|string',
                'street_1' => 'required|string',
                'city' => 'required|string',
                'state' => 'required|string',
                'postal_code' => 'required|string',
            ]);

            $accountResult = $this->createRazorpayLinkedAccountHandler->handle(CreateRazorpayLinkedAccountDTO::from([
                'accountId' => $this->getAuthenticatedAccountId(),
                'phone' => $requestData['phone'],
                'contactName' => $requestData['contact_name'],
                'companyName' => $requestData['company_name'],
                'businessType' => $requestData['business_type'],
                'category' => $requestData['category'],
                'subcategory' => $requestData['subcategory'],
                'pan' => $requestData['pan'],
                'gst' => $requestData['gst'] ?? null,
                'street1' => $requestData['street_1'],
                'city' => $requestData['city'],
                'state' => $requestData['state'],
                'postalCode' => $requestData['postal_code'],
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
