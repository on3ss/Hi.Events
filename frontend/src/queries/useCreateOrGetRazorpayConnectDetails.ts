import { useQuery } from '@tanstack/react-query';
import { IdParam, RazorpayLinkedDetails } from '../types';
import { accountClient } from "../api/account.client";
import { AxiosError } from "axios";

export const GET_RAZORPAY_CONNECT_ACCOUNT_DETAILS = 'getRazorpayConnectAccountDetails';

export const useCreateOrGetRazorpayConnectDetails = (accountId: IdParam, enabled: boolean, platform?: string) => {
    return useQuery<RazorpayLinkedDetails, AxiosError>({
        queryKey: [GET_RAZORPAY_CONNECT_ACCOUNT_DETAILS, accountId],

        queryFn: async (): Promise<RazorpayLinkedDetails> => {
            const { data } = await accountClient.getRazorpayConnectDetails(accountId);
            return data;
        },

        enabled: enabled,
        retry: false,
    });
};
