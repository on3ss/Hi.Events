import {useQuery, UseQueryOptions} from "@tanstack/react-query";
import {RazorpayLinkedAccountsResponse, IdParam} from "../types.ts";
import {accountClient} from "../api/account.client.ts";

export const GET_RAZORPAY_CONNECT_ACCOUNTS_QUERY_KEY = 'getRazorpayConnectAccounts';

export const useGetRazorpayConnectAccounts = (accountId: IdParam, options?: Partial<UseQueryOptions<RazorpayLinkedAccountsResponse>>) => {
    return useQuery<RazorpayLinkedAccountsResponse>({
        queryKey: [GET_RAZORPAY_CONNECT_ACCOUNTS_QUERY_KEY, accountId],
        queryFn: async () => {
            const {data} = await accountClient.getRazorpayConnectAccounts(accountId);
            return data;
        },
        ...options
    });
};