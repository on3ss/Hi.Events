import React, { useState, useEffect } from 'react';
import { Button } from '@mantine/core';
import { IconBrandCashapp } from '@tabler/icons-react';
import { t } from '@lingui/macro';
import { useCreateOrGetRazorpayConnectDetails } from '../../../queries/useCreateOrGetRazorpayConnectDetails';
import { useGetAccount } from '../../../queries/useGetAccount';
import { showSuccess } from '../../../utilites/notifications';

interface RazorpayConnectButtonProps {
    buttonText?: string;
    buttonIcon?: React.ReactNode;
    variant?: string;
    size?: string;
    fullWidth?: boolean;
    className?: string;
}

export const RazorpayConnectButton: React.FC<RazorpayConnectButtonProps> = ({
    buttonText,
    buttonIcon = <IconBrandCashapp size={20} />,
    variant = 'light',
    size = 'sm',
    fullWidth = false,
    className,
}) => {
    const [fetchRazorpayDetails, setFetchRazorpayDetails] = useState(false);
    const accountQuery = useGetAccount();
    const account = accountQuery.data;

    const razorpayDetailsQuery = useCreateOrGetRazorpayConnectDetails(
        account?.id || '',
        fetchRazorpayDetails && !!account?.id,
    );

    const razorpayDetails = razorpayDetailsQuery.data;

    useEffect(() => {
        if (fetchRazorpayDetails && !razorpayDetailsQuery.isLoading && razorpayDetails) {
            setFetchRazorpayDetails(false);
            showSuccess(t`Razorpay setup complete!`);
        }
    }, [fetchRazorpayDetails, razorpayDetailsQuery.isLoading, razorpayDetails]);

    const handleClick = () => {
        if (!razorpayDetails) {
            setFetchRazorpayDetails(true);
        } else {
            if (razorpayDetails.is_connect_setup_complete) {
                showSuccess(t`Razorpay setup is already complete.`);
                return;
            }
        }
    };

    const getButtonText = () => {
        if (buttonText) return buttonText;

        return t`Connect with Razorpay`;
    };

    return (
        <Button
            variant={variant}
            size={size}
            fullWidth={fullWidth}
            leftSection={buttonIcon}
            onClick={handleClick}
            className={className}
            loading={razorpayDetailsQuery.isLoading}
        >
            {getButtonText()}
        </Button>
    );
};
