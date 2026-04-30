import { useState, useEffect, useRef } from 'react';
import {
    Anchor,
    Button,
    Group,
    Text,
    ThemeIcon,
    Title,
    LoadingOverlay,
} from '@mantine/core';
import {
    IconCheck,
    IconAlertCircle,
    IconExternalLink,
    IconMoneybag,
} from '@tabler/icons-react';
import { t } from '@lingui/macro';
import { showSuccess, showError } from '../../utilites/notifications';
import { useGetRazorpayAccounts } from '../../queries/useGetRazorpayAccounts';
import { RazorpayOnboardingModal } from './RazorpayOnboardingModal';
import { trackEvent, AnalyticsEvents } from '../../utilites/analytics';
import { Account, CreateRazorpayLinkedAccountDTO } from '../../types';
import { useCreateRazorpayLinkedAccount } from '../../mutations/useCreateRazorpayLinkedAccount';
import { Card } from '../common/Card';

interface RazorpayPanelProps {
    account: Account;
}

export const RazorpayPanel = ({ account }: RazorpayPanelProps) => {
    const [showModal, setShowModal] = useState(false);

    const { data, isLoading, error, refetch } = useGetRazorpayAccounts(account.id);
    const createMutation = useCreateRazorpayLinkedAccount();

    const hasTracked = useRef(false);

    // Track analytics when a connected account appears
    useEffect(() => {
        if (data && !hasTracked.current) {
            const completed = data.razorpay_accounts.find((a) => a.is_onboarding_complete);
            if (completed) {
                hasTracked.current = true;
                trackEvent(AnalyticsEvents.RAZORPAY_CONNECTED);
            }
        }
    }, [data]);

    // Detect return from Razorpay (is_return / is_refresh params) and refetch
    useEffect(() => {
        if (typeof window === 'undefined') return;
        const params = new URLSearchParams(window.location.search);
        if (params.has('is_return') || params.has('is_refresh')) {
            refetch();
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    }, []);

    const handleConnect = () => setShowModal(true);

    const handleModalSubmit = (dto: CreateRazorpayLinkedAccountDTO) => {
        createMutation.mutate(dto, {
            onSuccess: () => {
                showSuccess(t`Razorpay account connected successfully!`);
                setShowModal(false);
                refetch();
            },
            onError: (err: any) => {
                if (err?.response?.status === 403) {
                    showError(t`Access denied. Please check your permissions.`);
                } else {
                    showError(err?.response?.data?.message || t`Failed to set up Razorpay.`);
                }
            },
        });
    };

    if ((error as any)?.response?.status === 403) {
        return (
            <Card>
                <Group gap="xs" mb="md">
                    <ThemeIcon size="lg" radius="md" variant="light">
                        <IconAlertCircle size={20} />
                    </ThemeIcon>
                    <Title order={2}>{t`Access Denied`}</Title>
                </Group>
                <Text size="md">{(error as any)?.response?.data?.message}</Text>
            </Card>
        );
    }

    if (isLoading) {
        return (
            <div style={{ position: 'relative', minHeight: 80 }}>
                <LoadingOverlay visible />
            </div>
        );
    }

    const razorpayAccount = data?.razorpay_accounts?.[0];
    const isConnected = razorpayAccount?.is_onboarding_complete === true;
    const isIncomplete = razorpayAccount && !isConnected;
    const initialEmail = account.email ?? '';
    const initialLegalBusinessName = account.name ?? '';

    return (
        <>
            <div style={{ marginTop: 20 }}>
                <Title mb={10} order={3}>{t`Razorpay Payment Processing`}</Title>

                {isConnected ? (
                    <>
                        <Group gap="xs" mb="md">
                            <ThemeIcon size="sm" variant="light" radius="xl" color="green">
                                <IconCheck size={16} />
                            </ThemeIcon>
                            <Text size="sm" fw={500}>
                                <b>{t`Connected to Razorpay`}</b>
                            </Text>
                        </Group>
                        <Text size="sm" c="dimmed" mb="lg">
                            {t`Your Razorpay account is active. Payouts are processed via your linked bank account.`}
                        </Text>
                        <Group gap="xs">
                            <Anchor
                                href="https://dashboard.razorpay.com/"
                                target="_blank"
                                size="sm"
                            >
                                <Group gap="xs" wrap="nowrap">
                                    <Text span>{t`Open Razorpay Dashboard`}</Text>
                                    <IconExternalLink size={14} />
                                </Group>
                            </Anchor>
                        </Group>
                    </>
                ) : isIncomplete ? (
                    <>
                        <Text size="sm" c="dimmed" mb="lg">
                            {t`Your onboarding is incomplete. Click below to finish.`}
                        </Text>
                        <Button
                            variant="light"
                            size="sm"
                            leftSection={<IconMoneybag size={20} />}
                            onClick={handleConnect}
                        >
                            {t`Finish Razorpay Setup`}
                        </Button>
                    </>
                ) : (
                    <>
                        <Text size="sm" c="dimmed" mb="lg">
                            {t`Accept domestic payments through Razorpay. Complete the form to start.`}
                        </Text>
                        <Button
                            variant="light"
                            size="sm"
                            leftSection={<IconMoneybag size={20} />}
                            onClick={handleConnect}
                        >
                            {t`Connect with Razorpay`}
                        </Button>
                    </>
                )}
            </div>

            <RazorpayOnboardingModal
                accountId={Number(account.id)}
                opened={showModal}
                onClose={() => setShowModal(false)}
                onSubmit={handleModalSubmit}
                isSubmitting={createMutation.isPending}
                initialEmail={initialEmail}
                initialLegalBusinessName={initialLegalBusinessName}
            />
        </>
    );
};