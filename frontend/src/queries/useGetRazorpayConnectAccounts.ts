import {useQuery, UseQueryOptions} from "@tanstack/react-query";
import {StripeConnectAccountsResponse, IdParam} from "../types.ts";
import {accountClient} from "../api/account.client.ts";

export const GET_STRIPE_CONNECT_ACCOUNTS_QUERY_KEY = 'getRazorpayConnectAccounts';

export const useGetRazorpayConnectAccounts = (accountId: IdParam, options?: Partial<UseQueryOptions<StripeConnectAccountsResponse>>) => {
    return useQuery<StripeConnectAccountsResponse>({
        queryKey: [GET_STRIPE_CONNECT_ACCOUNTS_QUERY_KEY, accountId],
        queryFn: async () => {
            const {data} = await accountClient.getRazorpayConnectAccounts(accountId);
            return data;
        },
        ...options
    });
};