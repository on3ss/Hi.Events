import { useMutation } from '@tanstack/react-query';
import { accountClient } from '../api/account.client';
import { UpdateRazorpayStakeholderStageDTO, CreateRazorpayLinkedAccountResponse } from '../types';
import { AxiosError } from 'axios';

export const useUpdateRazorpayStakeholder = () => {
    return useMutation<CreateRazorpayLinkedAccountResponse, AxiosError, UpdateRazorpayStakeholderStageDTO>({
        mutationFn: async (payload) => {
            const { data } = await accountClient.updateRazorpayStakeholder(payload.accountId, payload);
            return data;
        },
    });
};
