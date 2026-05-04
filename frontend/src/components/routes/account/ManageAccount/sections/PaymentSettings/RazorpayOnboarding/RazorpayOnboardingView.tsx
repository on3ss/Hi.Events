import { NotStartedState } from './states/NotStartedState';
import { ConnectedState } from './states/ConnectedState';
import { PendingState } from './states/PendingState';
import { ProcessingState } from './states/ProcessingState';
import { RetryFormState } from './states/RetryFormState';
import { SupportRequiredState } from './states/SupportRequiredState';
import { RazorpayAccount, RazorpayStatus } from '../../../../../../../types';

interface Props {
  status: RazorpayStatus;
  data?: RazorpayAccount;
  accountId: number;
  onRetry?: () => void;
  onStart?: () => void;
}

export const RazorpayOnboardingView: React.FC<Props> = ({
  status,
  data,
  accountId,
  onRetry,
  onStart,
}) => {
  switch (status) {
    case 'not_started':
      return <NotStartedState onStart={onStart} />;

    case 'active':
      return <ConnectedState />;

    case 'pending_activation':
      return <PendingState />;

    case 'initiated':
    case 'creating_remote':
    case 'created':
      return <ProcessingState />;

    case 'failed':
      return (
        <RetryFormState
          accountId={accountId}
          initialData={data}
        />
      );

    case 'orphaned_remote':
      return <SupportRequiredState onRetry={onRetry} />;

    default:
      return <ProcessingState />;
  }
};