import { z } from "zod";
import { AxiosError } from "axios";
import { useForm, useWatch, type Resolver } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
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
import { useCreateAccount } from "../hooks/useAccounts";
import { AccountCard } from "./AccountCard";
import { BankLogo } from "./BankLogo";

const formSchema = z.object({
  bank: z.string().min(1, "Bank is required"),
  name: z.string().min(1, "Account name is required"),
  type: z.enum(["Checking", "Savings"]),
  initialBalance: z.coerce.number(),
});

type FormValues = z.infer<typeof formSchema>;
type FormInputValues = Omit<FormValues, "initialBalance"> & {
  initialBalance: string | number;
};

interface ValidationError {
  errors?: Record<string, string[]>;
  message?: string;
}

function snakeToCamel(str: string): string {
  return str.replace(/_([a-z])/g, (g) => g[1].toUpperCase());
}

export function AddAccountForm() {
  const resolver = zodResolver(formSchema) as unknown as Resolver<
    FormInputValues,
    unknown,
    FormValues
  >;

  const form = useForm<FormInputValues, unknown, FormValues>({
    resolver,
    defaultValues: {
      bank: "",
      name: "",
      type: "Checking",
      initialBalance: 0,
    },
  });

  const selectedBankName = useWatch({ control: form.control, name: "bank" });
  const accountName = useWatch({ control: form.control, name: "name" });
  const accountType = useWatch({ control: form.control, name: "type" });
  const initialBalance = useWatch({
    control: form.control,
    name: "initialBalance",
  });
  const selectedBank = banks.find((b) => b.name === selectedBankName);
  const previewBalance =
    typeof initialBalance === "string"
      ? Number(initialBalance)
      : (initialBalance ?? 0);

  const createAccount = useCreateAccount();

  const onSubmit = (values: FormValues) => {
    createAccount.mutate(values, {
      onSuccess: () => {
        form.reset();
        // TODO: Close dialog
      },
      onError: (error: unknown) => {
        const axiosError = error as AxiosError;
        if (axiosError.response?.status === 422) {
          const data = axiosError.response.data as ValidationError;
          if (data.errors) {
            // Map backend field names (snake_case) to form field names (camelCase)
            Object.entries(data.errors).forEach(([field, messages]) => {
              const camelField = snakeToCamel(field) as keyof FormValues;
              const message = Array.isArray(messages) ? messages[0] : messages;
              form.setError(camelField, { message });
            });
          }
        }
      },
    });
  };

  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button>Add Account</Button>
      </DialogTrigger>
      <DialogContent className="max-w-sm">
        <DialogHeader>
          <DialogTitle>Add New Account</DialogTitle>
          <DialogDescription>
            Fill out the form below to add a new account to your dashboard.
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="bank"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Bank</FormLabel>
                  <Select
                    onValueChange={field.onChange}
                    defaultValue={field.value}
                  >
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
                  <Select
                    onValueChange={field.onChange}
                    defaultValue={field.value}
                  >
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
                    <Input {...field} type="number" placeholder="0.00" />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {selectedBank && (
              <div className="space-y-2">
                <div className="text-sm font-medium">Preview</div>
                <AccountCard
                  account={{
                    id: "preview",
                    name: accountName || "Account Name",
                    bank: selectedBank.name,
                    balance: Number.isFinite(previewBalance)
                      ? previewBalance
                      : 0,
                    type: accountType,
                    currency: "USD",
                    institution: selectedBank.name,
                    userId: "",
                    userName: "",
                    initialBalance: 0,
                    isClosed: false,
                    closeAt: null,
                    createdAt: "",
                    updatedAt: "",
                  }}
                />
              </div>
            )}

            <Button
              type="submit"
              className="w-full"
              disabled={createAccount.isPending}
            >
              Create Account
            </Button>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
