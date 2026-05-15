import { useState, useEffect } from 'react';
import {
  Modal, Stepper, Button, Group, TextInput, Select, Grid, Text
} from '@mantine/core';
import { useForm } from '@mantine/form';
import { t } from '@lingui/macro';
import { UpdateRazorpayBusinessStageDTO, UpdateRazorpayStakeholderStageDTO, UpdateRazorpaySettlementStageDTO, RazorpayAccount } from '../../../../../../../types';
import { useUpdateRazorpayBusiness } from '../../../../../../../mutations/useUpdateRazorpayBusiness';
import { useUpdateRazorpayStakeholder } from '../../../../../../../mutations/useUpdateRazorpayStakeholder';
import { useUpdateRazorpaySettlement } from '../../../../../../../mutations/useUpdateRazorpaySettlement';

interface RazorpayOnboardingModalProps {
    accountId: number;
    opened: boolean;
    onClose: () => void;
    onSuccess: () => void;
    initialEmail?: string;
    initialLegalBusinessName?: string;
    accountData?: RazorpayAccount;
}

export const RazorpayOnboardingModal = ({
  accountId,
  opened,
  onClose,
  onSuccess,
  initialEmail = '',
  initialLegalBusinessName = '',
  accountData,
}: RazorpayOnboardingModalProps) => {
  const [activeStep, setActiveStep] = useState(0);
  const [error, setError] = useState<string | null>(null);

  const businessMutation = useUpdateRazorpayBusiness();
  const stakeholderMutation = useUpdateRazorpayStakeholder();
  const settlementMutation = useUpdateRazorpaySettlement();

  useEffect(() => {
    if (accountData?.onboarding_data && opened) {
      if (accountData.onboarding_data.settlement) {
        setActiveStep(3);
      } else if (accountData.onboarding_data.stakeholder) {
        setActiveStep(2);
      } else if (accountData.onboarding_data.business) {
        setActiveStep(1);
      }
    }
  }, [opened]); // Only set the active step when the modal is opened

  const form = useForm({
    initialValues: {
      email: accountData?.onboarding_data?.business?.email || initialEmail,
      phone: accountData?.onboarding_data?.business?.phone || '',
      legalBusinessName: accountData?.onboarding_data?.business?.legalBusinessName || initialLegalBusinessName,
      businessType: accountData?.onboarding_data?.business?.businessType || 'partnership',
      contactName: accountData?.onboarding_data?.business?.contactName || '',

      regStreet1: accountData?.onboarding_data?.business?.registeredAddress?.street1 || '',
      regStreet2: accountData?.onboarding_data?.business?.registeredAddress?.street2 || '',
      regCity: accountData?.onboarding_data?.business?.registeredAddress?.city || '',
      regState: accountData?.onboarding_data?.business?.registeredAddress?.state || '',
      regPostalCode: accountData?.onboarding_data?.business?.registeredAddress?.postalCode || '',
      regCountry: accountData?.onboarding_data?.business?.registeredAddress?.country || 'IN',

      pan: accountData?.onboarding_data?.business?.pan || '',
      gst: accountData?.onboarding_data?.business?.gst || '',

      stakeName: accountData?.onboarding_data?.stakeholder?.name || '',
      stakeEmail: accountData?.onboarding_data?.stakeholder?.email || '',
      stakePan: accountData?.onboarding_data?.stakeholder?.pan || '',
      stakeStreet: accountData?.onboarding_data?.stakeholder?.residentialAddress?.street || '',
      stakeCity: accountData?.onboarding_data?.stakeholder?.residentialAddress?.city || '',
      stakeState: accountData?.onboarding_data?.stakeholder?.residentialAddress?.state || '',
      stakePostalCode: accountData?.onboarding_data?.stakeholder?.residentialAddress?.postalCode || '',
      stakeCountry: accountData?.onboarding_data?.stakeholder?.residentialAddress?.country || 'IN',

      accountNumber: accountData?.onboarding_data?.settlement?.accountNumber || '',
      ifscCode: accountData?.onboarding_data?.settlement?.ifscCode || '',
      beneficiaryName: accountData?.onboarding_data?.settlement?.beneficiaryName || '',
    },

    validate: {
      email: (v) => (/^\S+@\S+$/.test(v) ? null : t`Invalid email`),
      phone: (v) => (/^[6-9]\d{9}$/.test(v) ? null : t`Enter valid 10-digit mobile`),
      legalBusinessName: (v) => (v ? null : t`Required`),

      regStreet1: (v) => (v ? null : t`Required`),
      regCity: (v) => (v ? null : t`Required`),
      regState: (v) => (v ? null : t`Required`),
      regPostalCode: (v) =>
        /^[1-9][0-9]{5}$/.test(v) ? null : t`Invalid postal code`,

      pan: (v) =>
        v && !/^[A-Z]{5}[0-9]{4}[A-Z]$/.test(v) ? t`Invalid PAN` : null,

      stakeName: (v) => (v ? null : t`Required`),
      stakeEmail: (v) => (/^\S+@\S+$/.test(v) ? null : t`Invalid email`),
      stakeStreet: (v) => (v ? null : t`Required`),
      stakeCity: (v) => (v ? null : t`Required`),
      stakeState: (v) => (v ? null : t`Required`),
      stakePostalCode: (v) =>
        /^[1-9][0-9]{5}$/.test(v) ? null : t`Invalid postal code`,

      stakePan: (v) =>
        v && !/^[A-Z]{5}[0-9]{4}[A-Z]$/.test(v) ? t`Invalid PAN` : null,

      accountNumber: (v) => (v ? null : t`Required`),
      ifscCode: (v) =>
        /^[A-Z]{4}0[A-Z0-9]{6}$/.test(v) ? null : t`Invalid IFSC`,
      beneficiaryName: (v) => (v ? null : t`Required`),
    },
  });

  const stepFields = [
    ['legalBusinessName', 'email', 'phone', 'regStreet1', 'regCity', 'regState', 'regPostalCode', 'pan'],
    ['stakeName', 'stakeEmail', 'stakeStreet', 'stakeCity', 'stakeState', 'stakePostalCode', 'stakePan'],
    ['accountNumber', 'ifscCode', 'beneficiaryName'],
  ];

  const validateStep = () => {
    const errors = form.validate().errors;
    return stepFields[activeStep].some((f) => errors[f]);
  };

  const handleNext = async () => {
    if (validateStep()) return;

    setError(null);

    try {
        if (activeStep === 0) {
            const dto: UpdateRazorpayBusinessStageDTO = {
                accountId,
                email: form.values.email,
                phone: form.values.phone,
                legalBusinessName: form.values.legalBusinessName,
                businessType: form.values.businessType,
                contactName: form.values.contactName,
                registeredAddress: {
                    street1: form.values.regStreet1,
                    street2: form.values.regStreet2,
                    city: form.values.regCity,
                    state: form.values.regState,
                    postalCode: form.values.regPostalCode,
                    country: form.values.regCountry,
                },
                pan: form.values.pan,
                gst: form.values.gst,
            };
            await businessMutation.mutateAsync(dto);
            setActiveStep(1);
        } else if (activeStep === 1) {
            const dto: UpdateRazorpayStakeholderStageDTO = {
                accountId,
                stakeholder: {
                    name: form.values.stakeName,
                    email: form.values.stakeEmail,
                    pan: form.values.stakePan,
                    residentialAddress: {
                        street: form.values.stakeStreet,
                        city: form.values.stakeCity,
                        state: form.values.stakeState,
                        postalCode: form.values.stakePostalCode,
                        country: form.values.stakeCountry,
                    },
                },
            };
            await stakeholderMutation.mutateAsync(dto);
            setActiveStep(2);
        } else if (activeStep === 2) {
            const dto: UpdateRazorpaySettlementStageDTO = {
                accountId,
                settlement: {
                    accountNumber: form.values.accountNumber,
                    ifscCode: form.values.ifscCode,
                    beneficiaryName: form.values.beneficiaryName,
                },
            };
            await settlementMutation.mutateAsync(dto);
            setActiveStep(3);
        } else if (activeStep === 3) {
            onSuccess();
        }
    } catch (e: any) {
        setError(e?.response?.data?.message || e?.message || t`Something went wrong`);
    }
  };

  const handleBack = () => setActiveStep((s) => Math.max(s - 1, 0));

  const isSubmitting = businessMutation.isPending || stakeholderMutation.isPending || settlementMutation.isPending;

  return (
    <Modal opened={opened} onClose={onClose} title={t`Razorpay Onboarding`} size="70%" closeOnClickOutside={false}>
      
      {error && <Text c="red" mb="md">{error}</Text>}

      <Stepper active={activeStep} onStepClick={() => {}} mb="lg">

        {/* STEP 1 */}
        <Stepper.Step label={t`Business`} description={t`Company details`}>
          <Grid>

            <Grid.Col span={6}>
              <TextInput label={t`Email`} required readOnly {...form.getInputProps('email')} />
            </Grid.Col>

            <Grid.Col span={6}>
              <TextInput label={t`Phone`} required {...form.getInputProps('phone')} />
            </Grid.Col>

            <Grid.Col span={12}>
              <TextInput label={t`Legal Business Name`} required readOnly {...form.getInputProps('legalBusinessName')} />
            </Grid.Col>

            <Grid.Col span={6}>
              <Select
                label={t`Business Type`}
                data={[
                  { value: 'partnership', label: t`Partnership` },
                  { value: 'proprietorship', label: t`Proprietorship` },
                  { value: 'private_limited', label: t`Private Limited` },
                  { value: 'public_limited', label: t`Public Limited` },
                ]}
                {...form.getInputProps('businessType')}
              />
            </Grid.Col>

            <Grid.Col span={6}>
              <TextInput label={t`Contact Name`} {...form.getInputProps('contactName')} />
            </Grid.Col>

            <Grid.Col span={12}><Text fw={500}>{t`Registered Address`}</Text></Grid.Col>

            <Grid.Col span={12}><TextInput label={t`Street 1`} required {...form.getInputProps('regStreet1')} /></Grid.Col>
            <Grid.Col span={12}><TextInput label={t`Street 2`} {...form.getInputProps('regStreet2')} /></Grid.Col>

            <Grid.Col span={6}><TextInput label={t`City`} required {...form.getInputProps('regCity')} /></Grid.Col>
            <Grid.Col span={6}><TextInput label={t`State`} required {...form.getInputProps('regState')} /></Grid.Col>
            <Grid.Col span={6}><TextInput label={t`Postal Code`} required {...form.getInputProps('regPostalCode')} /></Grid.Col>
            <Grid.Col span={6}><TextInput label={t`Country`} readOnly {...form.getInputProps('regCountry')} /></Grid.Col>

            <Grid.Col span={6}><TextInput label={t`PAN`} required {...form.getInputProps('pan')} /></Grid.Col>
            <Grid.Col span={6}><TextInput label={t`GST`} {...form.getInputProps('gst')} /></Grid.Col>

          </Grid>
        </Stepper.Step>

        {/* STEP 2 */}
        <Stepper.Step label={t`Stakeholder`} description={t`Key person`}>
          <Grid>
            <Grid.Col span={6}><TextInput label={t`Name`} required {...form.getInputProps('stakeName')} /></Grid.Col>
            <Grid.Col span={6}><TextInput label={t`Email`} required {...form.getInputProps('stakeEmail')} /></Grid.Col>
            <Grid.Col span={6}><TextInput label={t`PAN`} required {...form.getInputProps('stakePan')} /></Grid.Col>

            <Grid.Col span={12}><Text fw={500}>{t`Residential Address`}</Text></Grid.Col>

            <Grid.Col span={12}><TextInput label={t`Street`} required {...form.getInputProps('stakeStreet')} /></Grid.Col>
            <Grid.Col span={6}><TextInput label={t`City`} required {...form.getInputProps('stakeCity')} /></Grid.Col>
            <Grid.Col span={6}><TextInput label={t`State`} required {...form.getInputProps('stakeState')} /></Grid.Col>
            <Grid.Col span={6}><TextInput label={t`Postal Code`} required {...form.getInputProps('stakePostalCode')} /></Grid.Col>
            <Grid.Col span={6}><TextInput label={t`Country`} readOnly {...form.getInputProps('stakeCountry')} /></Grid.Col>
          </Grid>
        </Stepper.Step>

        {/* STEP 3 */}
        <Stepper.Step label={t`Settlement`} description={t`Bank details`}>
          <Grid>
            <Grid.Col span={12}><TextInput label={t`Account Number`} required {...form.getInputProps('accountNumber')} /></Grid.Col>
            <Grid.Col span={12}><TextInput label={t`IFSC Code`} required {...form.getInputProps('ifscCode')} /></Grid.Col>
            <Grid.Col span={12}><TextInput label={t`Beneficiary Name`} required {...form.getInputProps('beneficiaryName')} /></Grid.Col>
          </Grid>
        </Stepper.Step>

        <Stepper.Completed>
            <Stack>
                <Text ta="center" size="lg" fw={500}>{t`Review & Submit`}</Text>
                <Text ta="center" c="dimmed">{t`Please confirm all your details are correct before submitting.`}</Text>
            </Stack>
        </Stepper.Completed>

      </Stepper>

      <Group justify="space-between">
        <Button variant="default" onClick={handleBack} disabled={activeStep === 0 || isSubmitting}>
          {t`Back`}
        </Button>

        {activeStep < 2 && (
          <Button onClick={handleNext} disabled={isSubmitting}>
            {t`Next`}
          </Button>
        )}

        {activeStep === 2 && (
          <Button onClick={handleNext} disabled={isSubmitting}>
            {t`Save & Review`}
          </Button>
        )}

        {activeStep === 3 && (
          <Button onClick={handleNext} disabled={isSubmitting}>
            {t`Finish`}
          </Button>
        )}
      </Group>

    </Modal>
  );
};
