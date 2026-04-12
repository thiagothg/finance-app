import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { api } from "@/lib/api";
import { type Account, type ApiAccount } from "../types";

export const accountKeys = {
  all: ["accounts"] as const,
  lists: () => [...accountKeys.all, "list"] as const,
  list: (params: Record<string, unknown>) =>
    [...accountKeys.lists(), params] as const,
  details: () => [...accountKeys.all, "detail"] as const,
  detail: (id: string) => [...accountKeys.details(), id] as const,
};

interface AccountsResponse {
  data: ApiAccount[];
  meta: {
    total_balance: number;
  };
}

function mapApiAccount(account: ApiAccount): Account {
  return {
    id: String(account.id),
    userId: String(account.user_id),
    userName: account.user_name,
    name: account.name,
    balance: account.balance,
    initialBalance: account.initial_balance,
    type: account.type,
    currency: account.currency,
    isClosed: account.is_closed,
    closeAt: account.close_at,
    institution: account.institution ?? account.bank,
    bank: account.bank,
    createdAt: account.created_at,
    updatedAt: account.updated_at,
  };
}

export function useAccounts() {
  return useQuery<{ data: Account[]; meta: AccountsResponse["meta"] }>({
    queryKey: accountKeys.list({ limit: -1 }),
    queryFn: async () => {
      const { data } = await api.get<AccountsResponse>("/accounts");
      return {
        data: data.data.map(mapApiAccount),
        meta: data.meta,
      };
    },
  });
}

function camelToSnake(str: string): string {
  return str.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
}

function convertKeysToSnakeCase(obj: Record<string, unknown>): Record<string, unknown> {
  const result: Record<string, unknown> = {};
  Object.entries(obj).forEach(([key, value]) => {
    result[camelToSnake(key)] = value;
  });
  return result;
}

function convertUpdatePayload(data: {
  bank: string;
  name: string;
  type: "Checking" | "Savings";
  initialBalance: number;
}): Record<string, unknown> {
  return convertKeysToSnakeCase(data);
}

export function useCreateAccount() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (newAccount: {
      bank: string;
      name: string;
      type: "Checking" | "Savings";
      initialBalance: number;
    }) => api.post("/accounts", convertKeysToSnakeCase(newAccount)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: accountKeys.lists() });
    },
  });
}

export function useUpdateAccount() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({
      id,
      data,
    }: {
      id: Account["id"];
      data: {
        bank: string;
        name: string;
        type: "Checking" | "Savings";
        initialBalance: number;
      };
    }) => api.put(`/accounts/${id}`, convertUpdatePayload(data)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: accountKeys.lists() });
      queryClient.invalidateQueries({ queryKey: accountKeys.details() });
    },
  });
}

export function useDeleteAccount() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: Account["id"]) => api.delete(`/accounts/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: accountKeys.lists() });
    },
  });
}
