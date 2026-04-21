import { useQuery } from '@tanstack/react-query';
import { IdParam, StripeConnectDetails } from '../types';
import { accountClient } from "../api/account.client";
import { AxiosError } from "axios";

export const GET_STRIPE_CONNECT_ACCOUNT_DETAILS = 'getStripeConnectAccountDetails';

export const useCreateOrGetRazorpayConnectDetails = (accountId: IdParam, enabled: boolean, platform?: string) => {
    return useQuery<StripeConnectDetails, AxiosError>({
        queryKey: [GET_STRIPE_CONNECT_ACCOUNT_DETAILS, accountId],

        queryFn: async (): Promise<StripeConnectDetails> => {
            const { data } = await accountClient.getRazorpayConnectDetails(accountId);
            return data;
        },

        enabled: enabled,
        retry: false,
    });
};
