import { useMutation } from '@tanstack/react-query';
import { accountClient } from '../api/account.client';
import { UpdateRazorpaySettlementStageDTO, CreateRazorpayLinkedAccountResponse } from '../types';
import { AxiosError } from 'axios';

export const useUpdateRazorpaySettlement = () => {
    return useMutation<CreateRazorpayLinkedAccountResponse, AxiosError, UpdateRazorpaySettlementStageDTO>({
        mutationFn: async (payload) => {
            const { data } = await accountClient.updateRazorpaySettlement(payload.accountId, payload);
            return data;
        },
    });
};
