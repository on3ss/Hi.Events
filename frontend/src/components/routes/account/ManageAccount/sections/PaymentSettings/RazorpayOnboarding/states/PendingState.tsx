import { Card, Text, Loader, Stack } from '@mantine/core';
import { t } from '@lingui/macro';

export const PendingState = () => (
  <Card withBorder>
    <Stack align="center">
      <Loader />
      <Text>{t`Verification in progress`}</Text>
    </Stack>
  </Card>
);