import { Card, Text, Stack } from '@mantine/core';
import { t } from '@lingui/macro';

export const ConnectedState = () => (
  <Card withBorder>
    <Stack>
      <Text fw={600}>{t`Razorpay account connected`}</Text>
      <Text size="sm" c="dimmed">
        {t`Your account is ready to receive payments.`}
      </Text>
    </Stack>
  </Card>
);