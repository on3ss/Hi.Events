import { Card, Text, Button, Stack } from '@mantine/core';
import { t } from '@lingui/macro';

export const NotStartedState = ({ onStart }: { onStart?: () => void }) => (
  <Card withBorder>
    <Stack>
      <Text fw={600}>{t`Accept payments with Razorpay`}</Text>
      <Text size="sm" c="dimmed">
        {t`Set up your Razorpay account to start receiving payments.`}
      </Text>
      <Button onClick={onStart}>{t`Connect with Razorpay`}</Button>
    </Stack>
  </Card>
);