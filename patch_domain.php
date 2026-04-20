<?php
$file = 'backend/app/DomainObjects/AccountDomainObject.php';
$content = file_get_contents($file);
$new_methods = <<<METHOD

    public function getAccountRazorpayPlatforms(): ?Collection
    {
        return \$this->razorpayPlatforms;
    }

    public function setAccountRazorpayPlatforms(Collection \$razorpayPlatforms): void
    {
        \$this->razorpayPlatforms = \$razorpayPlatforms;
    }

    public function getPrimaryRazorpayPlatform(): ?AccountRazorpayPlatformDomainObject
    {
        if (!\$this->razorpayPlatforms || \$this->razorpayPlatforms->isEmpty()) {
            return null;
        }

        return \$this->razorpayPlatforms
            ->filter(fn(\$platform) => \$platform->getRazorpaySetupCompletedAt() !== null)
            ->sortByDesc(fn(\$platform) => \$platform->getCreatedAt())
            ->first();
    }

    public function getActiveRazorpayAccountId(): ?string
    {
        return \$this->getPrimaryRazorpayPlatform()?->getRazorpayAccountId();
    }

    public function isRazorpaySetupComplete(): bool
    {
        return \$this->getPrimaryRazorpayPlatform() !== null;
    }
METHOD;

$content = str_replace(
    'public function getAccountStripePlatforms(): ?Collection',
    $new_methods . "\n\n    " . 'public function getAccountStripePlatforms(): ?Collection',
    $content
);
file_put_contents($file, $content);
