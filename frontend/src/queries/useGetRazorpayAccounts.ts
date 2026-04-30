import { useQuery } from '@tanstack/react-query';
import { IdParam, RazorpayAccountsResponse } from '../types';
import { accountClient } from '../api/account.client';
import { AxiosError } from 'axios';

export const useGetRazorpayAccounts = (accountId: IdParam, options?: { enabled?: boolean }) => {
    return useQuery<RazorpayAccountsResponse, AxiosError>({
        queryKey: ['razorpayAccounts', accountId],
        queryFn: async () => {
            const { data } = await accountClient.getRazorpayAccounts(accountId);
            return data;
        },
        enabled: !!accountId && (options?.enabled ?? true),
        retry: false,
    });
};