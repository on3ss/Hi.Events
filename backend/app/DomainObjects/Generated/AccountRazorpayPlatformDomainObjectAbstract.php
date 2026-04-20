<?php

namespace HiEvents\DomainObjects\Generated;

abstract class AccountRazorpayPlatformDomainObjectAbstract extends \HiEvents\DomainObjects\AbstractDomainObject
{
    final public const SINGULAR_NAME = 'account_razorpay_platform';
    final public const PLURAL_NAME = 'account_razorpay_platforms';
    final public const ID = 'id';
    final public const ACCOUNT_ID = 'account_id';
    final public const RAZORPAY_ACCOUNT_ID = 'razorpay_account_id';
    final public const RAZORPAY_SETUP_COMPLETED_AT = 'razorpay_setup_completed_at';
    final public const RAZORPAY_ACCOUNT_DETAILS = 'razorpay_account_details';
    final public const CREATED_AT = 'created_at';
    final public const UPDATED_AT = 'updated_at';
    final public const DELETED_AT = 'deleted_at';

    protected int $id;
    protected int $account_id;
    protected ?string $razorpay_account_id = null;
    protected ?string $razorpay_setup_completed_at = null;
    protected array|string|null $razorpay_account_details = null;
    protected ?string $created_at = null;
    protected ?string $updated_at = null;
    protected ?string $deleted_at = null;

    public function toArray(): array
    {
        return [
            'id' => $this->id ?? null,
            'account_id' => $this->account_id ?? null,
            'razorpay_account_id' => $this->razorpay_account_id ?? null,
            'razorpay_setup_completed_at' => $this->razorpay_setup_completed_at ?? null,
            'razorpay_account_details' => $this->razorpay_account_details ?? null,
            'created_at' => $this->created_at ?? null,
            'updated_at' => $this->updated_at ?? null,
            'deleted_at' => $this->deleted_at ?? null,
        ];
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setAccountId(int $account_id): self
    {
        $this->account_id = $account_id;
        return $this;
    }

    public function getAccountId(): int
    {
        return $this->account_id;
    }

    public function setRazorpayAccountId(?string $razorpay_account_id): self
    {
        $this->razorpay_account_id = $razorpay_account_id;
        return $this;
    }

    public function getRazorpayAccountId(): ?string
    {
        return $this->razorpay_account_id;
    }

    public function setRazorpaySetupCompletedAt(?string $razorpay_setup_completed_at): self
    {
        $this->razorpay_setup_completed_at = $razorpay_setup_completed_at;
        return $this;
    }

    public function getRazorpaySetupCompletedAt(): ?string
    {
        return $this->razorpay_setup_completed_at;
    }

    public function setRazorpayAccountDetails(array|string|null $razorpay_account_details): self
    {
        $this->razorpay_account_details = $razorpay_account_details;
        return $this;
    }

    public function getRazorpayAccountDetails(): array|string|null
    {
        return $this->razorpay_account_details;
    }

    public function setCreatedAt(?string $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setUpdatedAt(?string $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }

    public function setDeletedAt(?string $deleted_at): self
    {
        $this->deleted_at = $deleted_at;
        return $this;
    }

    public function getDeletedAt(): ?string
    {
        return $this->deleted_at;
    }
}
