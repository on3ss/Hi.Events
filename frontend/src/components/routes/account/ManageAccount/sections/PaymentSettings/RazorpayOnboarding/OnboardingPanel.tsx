import { useEffect, useRef, useState } from 'react';
import { Title, LoadingOverlay } from '@mantine/core';
import { t } from '@lingui/macro';
import { useGetRazorpayAccounts } from '../../../../../../../queries/useGetRazorpayAccounts';
import { useCreateRazorpayLinkedAccount } from '../../../../../../../mutations/useCreateRazorpayLinkedAccount';
import { Account, RazorpayStatus } from '../../../../../../../types';
import { RazorpayOnboardingView } from './RazorpayOnboardingView';
import { RazorpayOnboardingModal } from './OnboardingModal';

interface Props {
  account: Account;
}

const isValidStatus = (s: any): s is RazorpayStatus =>
  [
    'not_started',
    'initiated',
    'creating_remote',
    'created',
    'pending_activation',
    'active',
    'failed',
    'orphaned_remote',
  ].includes(s);

export const RazorpayPanel: React.FC<Props> = ({ account }) => {
  const { data, isLoading, refetch } = useGetRazorpayAccounts(account.id);
  const createMutation = useCreateRazorpayLinkedAccount();

  const [showModal, setShowModal] = useState(false);
  const hasTracked = useRef(false);

  useEffect(() => {
    if (data && !hasTracked.current) {
      const completed = data.razorpay_accounts.find(
        (a) => a.status === 'active'
      );
      if (completed) hasTracked.current = true;
    }
  }, [data]);

  if (isLoading) {
    return <LoadingOverlay visible />;
  }

  const apiAccount = data?.razorpay_accounts?.[0];

  const status: RazorpayStatus = !apiAccount
    ? 'not_started'
    : isValidStatus(apiAccount.status)
      ? apiAccount.status
      : 'initiated';

  const initialData = apiAccount
    ? {
        id: apiAccount.id,
        status,
        razorpay_account_id: apiAccount.razorpay_account_id ?? null,
        email: apiAccount.email ?? account.email,
        legal_business_name:
          apiAccount.legal_business_name ?? account.name,
      }
    : undefined;

  const handleStart = () => setShowModal(true);

  const handleSubmit = (dto: any) => {
    createMutation.mutate(dto, {
      onSuccess: () => {
        setShowModal(false);
        refetch();
      },
    });
  };

  return (
    <>
      <Title mb={10} order={3}>
        {t`Razorpay Payment Processing`}
      </Title>

      <RazorpayOnboardingView
        status={status}
        data={initialData}
        accountId={Number(account.id)}
        onRetry={refetch}
        onStart={handleStart}
      />

      <RazorpayOnboardingModal
        accountId={Number(account.id)}
        opened={showModal}
        onClose={() => setShowModal(false)}
        onSubmit={handleSubmit}
        isSubmitting={createMutation.isPending}
        initialEmail={account.email}
        initialLegalBusinessName={account.name}
      />
    </>
  );
};