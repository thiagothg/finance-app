import { useEffect } from "react";
import { z } from "zod";
import { AxiosError } from "axios";
import { useForm, type Resolver } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { banks } from "@/features/management/accounts/data/banks";
import { useUpdateAccount } from "../hooks/useAccounts";
import { type Account } from "../types";
import { BankLogo } from "./BankLogo";

type FormValues = {
  bank: string;
  name: string;
  type: "Checking" | "Savings";
  initialBalance: number;
};

type FormInputValues = Omit<FormValues, "initialBalance"> & {
  initialBalance: string | number;
};

const formSchema = z.object({
  bank: z.string().min(1, "Bank is required"),
  name: z.string().min(1, "Account name is required"),
  type: z.enum(["Checking", "Savings"]),
  initialBalance: z.coerce.number(),
});

type FormFieldName = keyof FormInputValues;

interface ValidationError {
  errors?: Record<string, string[]>;
  message?: string;
}

function snakeToCamel(str: string): string {
  return str.replace(/_([a-z])/g, (g) => g[1].toUpperCase());
}

interface EditAccountFormProps {
  account: Account;
  onSuccess?: () => void;
}

export function EditAccountForm({ account, onSuccess }: EditAccountFormProps) {
  const { bank, name, type, initialBalance } = account;
  const resolver = zodResolver(formSchema) as unknown as Resolver<
    FormInputValues,
    unknown,
    FormValues
  >;

  const form = useForm<FormInputValues, unknown, FormValues>({
    resolver,
    defaultValues: {
      bank,
      name,
      type,
      initialBalance,
    },
  });

  useEffect(() => {
    form.reset({
      bank,
      name,
      type,
      initialBalance,
    });
  }, [bank, name, type, initialBalance, form]);

  const updateAccount = useUpdateAccount();

  const onSubmit = (values: FormValues) => {
    updateAccount.mutate(
      { id: account.id, data: values },
      {
        onSuccess: () => {
          onSuccess?.();
        },
        onError: (error: unknown) => {
          const axiosError = error as AxiosError;
          if (axiosError.response?.status === 422) {
            const data = axiosError.response.data as ValidationError;
            if (data.errors) {
              Object.entries(data.errors).forEach(([field, messages]) => {
                const camelField = snakeToCamel(field) as FormFieldName;
                const message = Array.isArray(messages)
                  ? messages[0]
                  : messages;
                form.setError(camelField, { message });
              });
            }
          }
        },
      }
    );
  };

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
        <FormField
          control={form.control}
          name="bank"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Bank</FormLabel>
              <Select onValueChange={field.onChange} value={field.value}>
                <FormControl>
                  <SelectTrigger>
                    <SelectValue placeholder="Select a bank" />
                  </SelectTrigger>
                </FormControl>
                <SelectContent>
                  {banks.map((bank) => (
                    <SelectItem key={bank.id} value={bank.name}>
                      <div className="flex items-center">
                        <BankLogo bank={bank} className="mr-2 h-6 w-6" />
                        <span>{bank.name}</span>
                      </div>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="name"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Account Name</FormLabel>
              <FormControl>
                <Input {...field} placeholder="e.g., Main Checking" />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="type"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Account Type</FormLabel>
              <Select onValueChange={field.onChange} value={field.value}>
                <FormControl>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                </FormControl>
                <SelectContent>
                  <SelectItem value="Checking">Checking</SelectItem>
                  <SelectItem value="Savings">Savings</SelectItem>
                </SelectContent>
              </Select>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="initialBalance"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Initial Balance</FormLabel>
              <FormControl>
                <Input
                  {...field}
                  type="number"
                  placeholder="0.00"
                  value={field.value ?? ""}
                  onChange={(e) => field.onChange(e.target.value)}
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <Button
          type="submit"
          className="w-full"
          disabled={updateAccount.isPending}
        >
          {updateAccount.isPending ? "Saving..." : "Save Changes"}
        </Button>
      </form>
    </Form>
  );
}
