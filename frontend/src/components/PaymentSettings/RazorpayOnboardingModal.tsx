import { useState } from 'react';
import { Modal, Stepper, Button, Group, TextInput, Select, Grid, Text } from '@mantine/core';
import { useForm } from '@mantine/form';
import { t } from '@lingui/macro';
import { CreateRazorpayLinkedAccountDTO } from '../../types';

interface RazorpayOnboardingModalProps {
    accountId: number;
    opened: boolean;
    onClose: () => void;
    onSubmit: (dto: CreateRazorpayLinkedAccountDTO) => void;
    isSubmitting: boolean;
}

export const RazorpayOnboardingModal = ({
    accountId,
    opened,
    onClose,
    onSubmit,
    isSubmitting,
}: RazorpayOnboardingModalProps) => {
    const [activeStep, setActiveStep] = useState(0);

    const form = useForm({
        initialValues: {
            email: '',
            phone: '',
            legalBusinessName: '',
            businessType: 'partnership',
            contactName: '',
            regStreet1: '',
            regStreet2: '',
            regCity: '',
            regState: '',
            regPostalCode: '',
            regCountry: 'IN',
            pan: '',
            gst: '',
            stakeName: '',
            stakeEmail: '',
            stakePan: '',
            stakeStreet: '',
            stakeCity: '',
            stakeState: '',
            stakePostalCode: '',
            stakeCountry: 'IN',
            accountNumber: '',
            ifscCode: '',
            beneficiaryName: '',
        },
        validate: {
            legalBusinessName: (v) => (v ? null : t`Required`),
            email: (v) => (/^\S+@\S+$/.test(v) ? null : t`Invalid email`),
            phone: (v) => (v.trim().length >= 10 ? null : t`Phone must be at least 10 digits`),
            regStreet1: (v) => (v ? null : t`Required`),
            regCity: (v) => (v ? null : t`Required`),
            regState: (v) => (v ? null : t`Required`),
            regPostalCode: (v) => (v ? null : t`Required`),
            stakeName: (v) => (v ? null : t`Required`),
            stakeEmail: (v) => (/^\S+@\S+$/.test(v) ? null : t`Invalid email`),
            stakeStreet: (v) => (v ? null : t`Required`),
            stakeCity: (v) => (v ? null : t`Required`),
            stakeState: (v) => (v ? null : t`Required`),
            stakePostalCode: (v) => (v ? null : t`Required`),
            accountNumber: (v) => (v ? null : t`Required`),
            ifscCode: (v) => (v ? null : t`Required`),
            beneficiaryName: (v) => (v ? null : t`Required`),
        },
    });

    const buildDto = (): CreateRazorpayLinkedAccountDTO => ({
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
        settlement: {
            accountNumber: form.values.accountNumber,
            ifscCode: form.values.ifscCode,
            beneficiaryName: form.values.beneficiaryName,
        },
    });

    const handleNext = () => {
        const stepValidation = [
            ['legalBusinessName', 'email', 'phone', 'regStreet1', 'regCity', 'regState', 'regPostalCode'],
            ['stakeName', 'stakeEmail', 'stakeStreet', 'stakeCity', 'stakeState', 'stakePostalCode'],
            ['accountNumber', 'ifscCode', 'beneficiaryName'],
        ];
        const fieldsToValidate = stepValidation[activeStep];
        const errors = form.validate();
        const stepHasErrors = fieldsToValidate.some((f) => errors.hasOwnProperty(f));
        if (!stepHasErrors) {
            setActiveStep((c) => Math.min(c + 1, 3));
        }
    };

    const handleBack = () => setActiveStep((c) => Math.max(c - 1, 0));

    const handleSubmit = () => {
        if (form.validate().hasErrors) return;
        onSubmit(buildDto());
    };

    return (
        <Modal
            opened={opened}
            onClose={onClose}
            title={t`Razorpay Onboarding`}
            size="lg"
            closeOnClickOutside={false}
        >
            <Stepper active={activeStep} onStepClick={setActiveStep} mb="lg">
                <Stepper.Step label={t`Business`} description={t`Company details`}>
                    <Grid>
                        <Grid.Col span={6}>
                            <TextInput label={t`Email`} required {...form.getInputProps('email')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`Phone`} required {...form.getInputProps('phone')} />
                        </Grid.Col>
                        <Grid.Col span={12}>
                            <TextInput label={t`Legal Business Name`} required {...form.getInputProps('legalBusinessName')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <Select
                                label={t`Business Type`}
                                required
                                data={['partnership', 'proprietorship', 'private_limited', 'public_limited']}
                                {...form.getInputProps('businessType')}
                            />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`Contact Name`} {...form.getInputProps('contactName')} />
                        </Grid.Col>
                        <Grid.Col span={12}>
                            <Text size="sm" fw={500} mb={4}>{t`Registered Address`}</Text>
                        </Grid.Col>
                        <Grid.Col span={12}>
                            <TextInput label={t`Street 1`} required {...form.getInputProps('regStreet1')} />
                        </Grid.Col>
                        <Grid.Col span={12}>
                            <TextInput label={t`Street 2`} {...form.getInputProps('regStreet2')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`City`} required {...form.getInputProps('regCity')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`State`} required {...form.getInputProps('regState')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`Postal Code`} required {...form.getInputProps('regPostalCode')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`Country`} {...form.getInputProps('regCountry')} disabled />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`PAN`} {...form.getInputProps('pan')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`GST`} {...form.getInputProps('gst')} />
                        </Grid.Col>
                    </Grid>
                </Stepper.Step>

                <Stepper.Step label={t`Stakeholder`} description={t`Key person`}>
                    <Grid>
                        <Grid.Col span={6}>
                            <TextInput label={t`Name`} required {...form.getInputProps('stakeName')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`Email`} required {...form.getInputProps('stakeEmail')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`PAN`} {...form.getInputProps('stakePan')} />
                        </Grid.Col>
                        <Grid.Col span={12}>
                            <Text size="sm" fw={500} mb={4}>{t`Residential Address`}</Text>
                        </Grid.Col>
                        <Grid.Col span={12}>
                            <TextInput label={t`Street`} required {...form.getInputProps('stakeStreet')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`City`} required {...form.getInputProps('stakeCity')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`State`} required {...form.getInputProps('stakeState')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`Postal Code`} required {...form.getInputProps('stakePostalCode')} />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput label={t`Country`} {...form.getInputProps('stakeCountry')} disabled />
                        </Grid.Col>
                    </Grid>
                </Stepper.Step>

                <Stepper.Step label={t`Settlement`} description={t`Bank details`}>
                    <Grid>
                        <Grid.Col span={12}>
                            <TextInput label={t`Account Number`} required {...form.getInputProps('accountNumber')} />
                        </Grid.Col>
                        <Grid.Col span={12}>
                            <TextInput label={t`IFSC Code`} required {...form.getInputProps('ifscCode')} />
                        </Grid.Col>
                        <Grid.Col span={12}>
                            <TextInput label={t`Beneficiary Name`} required {...form.getInputProps('beneficiaryName')} />
                        </Grid.Col>
                    </Grid>
                </Stepper.Step>
                <Stepper.Completed>
                    <Text ta="center" size="lg" fw={500} mb="md">{t`Review & Submit`}</Text>
                    <Button
                        fullWidth
                        onClick={handleSubmit}
                        loading={isSubmitting}
                    >
                        {t`Submit Onboarding Details`}
                    </Button>
                </Stepper.Completed>
            </Stepper>

            <Group justify="space-between" mt="xl">
                <Button variant="default" onClick={handleBack} disabled={activeStep === 0}>
                    {t`Back`}
                </Button>
                {activeStep < 2 && (
                    <Button onClick={handleNext}>{t`Next`}</Button>
                )}
                {activeStep === 2 && (
                    <Button onClick={handleNext}>{t`Review`}</Button>
                )}
            </Group>
        </Modal>
    );
};