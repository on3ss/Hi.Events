import { Card, Text, Loader, Stack } from '@mantine/core';
import { t } from '@lingui/macro';

export const ProcessingState = () => (
  <Card withBorder>
    <Stack align="center">
      <Loader />
      <Text>{t`Setting up your account...`}</Text>
    </Stack>
  </Card>
);