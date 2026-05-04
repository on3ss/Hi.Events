import { Card, Text, Stack, Button } from '@mantine/core';
import { t } from '@lingui/macro';

export const SupportRequiredState = ({ onRetry }: { onRetry?: () => void }) => (
  <Card withBorder>
    <Stack>
      <Text fw={600}>{t`Action required`}</Text>
      <Text size="sm" c="dimmed">
        {t`We found an existing account but couldn't complete setup automatically.`}
      </Text>
      <Button onClick={onRetry}>{t`Retry`}</Button>
    </Stack>
  </Card>
);