import { useMutation } from '@tanstack/react-query';
import { accountClient } from '../api/account.client';
import { UpdateRazorpayBusinessStageDTO, CreateRazorpayLinkedAccountResponse } from '../types';
import { AxiosError } from 'axios';

export const useUpdateRazorpayBusiness = () => {
    return useMutation<CreateRazorpayLinkedAccountResponse, AxiosError, UpdateRazorpayBusinessStageDTO>({
        mutationFn: async (payload) => {
            const { data } = await accountClient.updateRazorpayBusiness(payload.accountId, payload);
            return data;
        },
    });
};
