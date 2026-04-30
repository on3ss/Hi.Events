import { useMutation } from '@tanstack/react-query';
import { accountClient } from '../api/account.client';
import { CreateRazorpayLinkedAccountDTO, CreateRazorpayLinkedAccountResponse } from '../types';
import { AxiosError } from 'axios';

export const useCreateRazorpayLinkedAccount = () => {
    return useMutation<CreateRazorpayLinkedAccountResponse, AxiosError, CreateRazorpayLinkedAccountDTO>({
        mutationFn: async (payload) => {
            const { data } = await accountClient.createRazorpayLinkedAccount(payload.accountId, payload);
            return data;
        },
    });
};