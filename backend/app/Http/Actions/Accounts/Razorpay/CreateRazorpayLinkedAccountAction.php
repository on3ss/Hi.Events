<?php

namespace HiEvents\Http\Actions\Accounts\Razorpay;

use HiEvents\DomainObjects\AccountDomainObject;
use HiEvents\DomainObjects\Enums\Role;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\CreateRazorpayLinkedAccountHandler;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\CreateRazorpayLinkedAccountDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\RegisteredAddressDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\StakeholderDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\ResidentialAddressDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\SettlementDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CreateRazorpayLinkedAccountAction extends BaseAction
{
    public function __construct(
        private readonly CreateRazorpayLinkedAccountHandler $handler,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(Request $request, int $accountId): JsonResponse
    {
        $this->isActionAuthorized($accountId, AccountDomainObject::class, Role::ADMIN);

        $dto = $this->buildDto($request, $accountId);
        $response = $this->handler->handle($dto);

        return response()->json($response);
    }

    private function buildDto(Request $request, int $accountId): CreateRazorpayLinkedAccountDTO
    {
        return new CreateRazorpayLinkedAccountDTO(
            accountId: $accountId,
            email: $request->input('email'),
            phone: $request->input('phone'),
            legalBusinessName: $request->input('legalBusinessName'),
            businessType: $request->input('businessType', 'partnership'),
            contactName: $request->input('contactName'),
            profileCategory: $request->input('profileCategory', 'healthcare'),
            profileSubcategory: $request->input('profileSubcategory', 'clinic'),
            registeredAddress: $this->mapRegisteredAddress($request),
            pan: $request->input('pan'),
            gst: $request->input('gst'),
            stakeholder: $this->mapStakeholder($request),
            settlement: $this->mapSettlement($request),
        );
    }

    private function mapRegisteredAddress(Request $request): ?RegisteredAddressDTO
    {
        $reg = $request->input('registeredAddress');
        if (!$reg) return null;
        return new RegisteredAddressDTO(
            street1: $reg['street1'] ?? '',
            street2: $reg['street2'] ?? '',
            city: $reg['city'] ?? '',
            state: $reg['state'] ?? '',
            postalCode: $reg['postalCode'] ?? '',
            country: $reg['country'] ?? 'IN',
        );
    }

    private function mapStakeholder(Request $request): ?StakeholderDTO
    {
        $stk = $request->input('stakeholder');
        if (!$stk) return null;
        $resAddr = $stk['residentialAddress'] ?? [];
        return new StakeholderDTO(
            name: $stk['name'] ?? '',
            email: $stk['email'] ?? '',
            pan: $stk['pan'] ?? null,
            residentialAddress: new ResidentialAddressDTO(
                street: $resAddr['street'] ?? '',
                city: $resAddr['city'] ?? '',
                state: $resAddr['state'] ?? '',
                postalCode: $resAddr['postalCode'] ?? '',
                country: $resAddr['country'] ?? 'IN',
            ),
        );
    }

    private function mapSettlement(Request $request): ?SettlementDTO
    {
        $setl = $request->input('settlement');
        if (!$setl) return null;
        return new SettlementDTO(
            accountNumber: $setl['accountNumber'] ?? '',
            ifscCode: $setl['ifscCode'] ?? '',
            beneficiaryName: $setl['beneficiaryName'] ?? '',
        );
    }
}