import axios from "axios";
import { useForm, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";

import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import {
  InputOTP,
  InputOTPGroup,
  InputOTPSlot,
} from "@/components/ui/input-otp";
import { useAuthStore } from "@/stores/authStore";

import { useValidateCode } from "../hooks/useValidateCode";

const formSchema = z.object({
  otp: z
    .string()
    .length(6, "Please enter the 6-digit code.")
    .regex(/^\d+$/, "Please enter the 6-digit code."),
});

type OtpFormProps = React.HTMLAttributes<HTMLFormElement>;

export function OtpForm({ className, ...props }: OtpFormProps) {
  const pendingVerification = useAuthStore(
    (state) => state.pendingVerification
  );
  const validateCode = useValidateCode();

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: { otp: "" },
  });

  const otp =
    useWatch({
      control: form.control,
      name: "otp",
    }) ?? "";

  const isLoading = validateCode.isPending;
  const rootError = form.formState.errors.root?.message;

  function onSubmit(data: z.infer<typeof formSchema>) {
    if (!pendingVerification) {
      return;
    }

    form.clearErrors("root");

    validateCode.mutate(
      {
        email: pendingVerification.email,
        code: data.otp,
      },
      {
        onError: (error) => {
          if (axios.isAxiosError(error) && error.response?.status === 422) {
            const responseErrors = error.response.data?.errors;

            if (
              responseErrors &&
              typeof responseErrors === "object" &&
              !Array.isArray(responseErrors)
            ) {
              const codeErrors = responseErrors.code;

              if (
                Array.isArray(codeErrors) &&
                typeof codeErrors[0] === "string"
              ) {
                form.setError("otp", {
                  type: "server",
                  message: codeErrors[0],
                });
                return;
              }
            }
          }

          form.setError("root", {
            type: "server",
            message: "Invalid or expired code. Please try again.",
          });
        },
      }
    );
  }

  return (
    <Form {...form}>
      <form
        onSubmit={form.handleSubmit(onSubmit)}
        className={cn("grid gap-2", className)}
        {...props}
      >
        {rootError ? (
          <div className="rounded-2xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
            {rootError}
          </div>
        ) : null}

        <FormField
          control={form.control}
          name="otp"
          render={({ field }) => (
            <FormItem>
              <FormLabel className="sr-only">One-Time Password</FormLabel>
              <FormControl>
                <InputOTP
                  maxLength={6}
                  {...field}
                  containerClassName='justify-between sm:[&>[data-slot="input-otp-group"]>div]:w-12'
                >
                  <InputOTPGroup>
                    <InputOTPSlot index={0} />
                    <InputOTPSlot index={1} />
                  </InputOTPGroup>
                  <InputOTPGroup>
                    <InputOTPSlot index={2} />
                    <InputOTPSlot index={3} />
                  </InputOTPGroup>
                  <InputOTPGroup>
                    <InputOTPSlot index={4} />
                    <InputOTPSlot index={5} />
                  </InputOTPGroup>
                </InputOTP>
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <Button className="mt-2" disabled={otp.length < 6 || isLoading}>
          Verify
        </Button>
      </form>
    </Form>
  );
}
