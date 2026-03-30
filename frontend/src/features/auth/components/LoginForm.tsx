import axios from "axios";
import { ArrowRight, Lock, Mail } from "lucide-react";
import { useForm, type FieldErrors, type Resolver } from "react-hook-form";
import { useTranslation } from "react-i18next";
import { z } from "zod";

import InputError from "@/components/InputError";
import PasswordInput from "@/components/PasswordInput";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Spinner } from "@/components/ui/spinner";

import { useLogin } from "../hooks/useLogin";

const loginSchema = z.object({
  email: z.string().email("auth.login.errors.email_invalid"),
  password: z.string().min(8, "auth.login.errors.password_min"),
});

type LoginFormValues = z.infer<typeof loginSchema>;

const loginResolver: Resolver<LoginFormValues> = async (values) => {
  const result = loginSchema.safeParse(values);

  if (result.success) {
    return {
      values: result.data,
      errors: {},
    };
  }

  const fieldErrors = result.error.issues.reduce<FieldErrors<LoginFormValues>>(
    (accumulator, issue) => {
      const fieldName = issue.path[0];

      if (typeof fieldName !== "string") {
        return accumulator;
      }

      accumulator[fieldName as keyof LoginFormValues] = {
        type: issue.code,
        message: issue.message,
      };

      return accumulator;
    },
    {},
  );

  return {
    values: {},
    errors: fieldErrors,
  };
};

function getErrorMessage(
  value: unknown,
  fallbackKey: string,
  t: (key: string) => string,
): string {
  if (typeof value === "string" && value.length > 0) {
    return t(value);
  }

  return t(fallbackKey);
}

export function LoginForm() {
  const { t } = useTranslation();
  const login = useLogin();
  const form = useForm<LoginFormValues>({
    resolver: loginResolver,
    defaultValues: {
      email: "",
      password: "",
    },
  });

  const emailError = form.formState.errors.email?.message;
  const passwordError = form.formState.errors.password?.message;
  const rootError = form.formState.errors.root?.message;

  function onSubmit(values: LoginFormValues): void {
    form.clearErrors("root");

    login.mutate(values, {
      onError: (error) => {
        if (axios.isAxiosError(error) && error.response?.status === 422) {
          const responseErrors = error.response.data?.errors;

          if (
            responseErrors &&
            typeof responseErrors === "object" &&
            !Array.isArray(responseErrors)
          ) {
            Object.entries(responseErrors).forEach(([field, messages]) => {
              const firstMessage = Array.isArray(messages) ? messages[0] : null;

              if (field === "email" || field === "password") {
                form.setError(field, {
                  type: "server",
                  message:
                    typeof firstMessage === "string"
                      ? firstMessage
                      : `auth.login.errors.${field}_invalid`,
                });
              }
            });

            return;
          }
        }

        if (axios.isAxiosError(error) && error.response?.status === 401) {
          form.setError("root", {
            type: "server",
            message: "auth.login.errors.invalid_credentials",
          });
          return;
        }

        if (axios.isAxiosError(error) && error.response?.status === 429) {
          form.setError("root", {
            type: "server",
            message: "auth.login.errors.too_many_attempts",
          });
          return;
        }

        form.setError("root", {
          type: "server",
          message: "auth.login.errors.unexpected",
        });
      },
    });
  }

  return (
    <form
      onSubmit={form.handleSubmit(onSubmit)}
      className="space-y-5"
      noValidate
    >
      {rootError ? (
        <div className="rounded-2xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
          {getErrorMessage(
            rootError,
            "auth.login.errors.unexpected",
            t as (key: string) => string,
          )}
        </div>
      ) : null}

      <div className="space-y-2">
        <label
          htmlFor="email"
          className="sr-only"
        >
          {t("auth.login.email")}
        </label>
        <div className="relative">
          <Mail className="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-muted-foreground" />
          <Input
            id="email"
            type="email"
            autoComplete="email"
            placeholder={t("auth.login.email_placeholder")}
            className="h-14 rounded-xl border-border/70 bg-card pl-12 text-sm shadow-[0_1px_2px_hsl(var(--foreground)/0.04)] focus-visible:border-primary"
            aria-invalid={emailError ? "true" : "false"}
            {...form.register("email")}
          />
        </div>
        <InputError
          message={
            emailError
              ? getErrorMessage(
                  emailError,
                  "auth.login.errors.email_invalid",
                  t as (key: string) => string,
                )
              : undefined
          }
        />
      </div>

      <div className="space-y-2">
        <label
          htmlFor="password"
          className="sr-only"
        >
          {t("auth.login.password")}
        </label>
        <div className="relative">
          <Lock className="pointer-events-none absolute top-1/2 left-4 z-10 size-5 -translate-y-1/2 text-muted-foreground" />
          <PasswordInput
            id="password"
            autoComplete="current-password"
            placeholder={t("auth.login.password_placeholder")}
            className="h-14 rounded-xl border-border/70 bg-card pl-12 text-sm shadow-[0_1px_2px_hsl(var(--foreground)/0.04)] focus-visible:border-primary"
            aria-invalid={passwordError ? "true" : "false"}
            {...form.register("password")}
          />
        </div>
        <InputError
          message={
            passwordError
              ? getErrorMessage(
                  passwordError,
                  "auth.login.errors.password_min",
                  t as (key: string) => string,
                )
              : undefined
          }
        />
      </div>

      <Button
        type="submit"
        size="lg"
        className="h-14 w-full rounded-xl text-base font-bold"
        disabled={login.isPending}
      >
        {login.isPending ? <Spinner className="size-5" /> : null}
        {login.isPending ? t("common.loading") : t("auth.login.submit")}
        <ArrowRight className="size-5" />
      </Button>
    </form>
  );
}
