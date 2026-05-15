import { RazorpayAccount } from "../../../../../../../../types";
import { RazorpayOnboardingModal } from "../OnboardingModal";

export const RetryFormState = ({
  accountId,
  initialData,
}: {
  accountId: number;
  initialData?: RazorpayAccount;
}) => {
  return (
    <RazorpayOnboardingModal
      accountId={accountId}
      opened={true}
      onClose={() => {}}
      onSuccess={() => {}}
      initialEmail={initialData?.email || ''}
      initialLegalBusinessName={initialData?.legal_business_name || ''}
      accountData={initialData}
    />
  );
};