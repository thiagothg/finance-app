import axios from "axios";
import { ShieldCheck } from "lucide-react";
import { useRef } from "react";
import {
  useForm,
  useWatch,
  type FieldErrors,
  type Resolver,
} from "react-hook-form";
import { useTranslation } from "react-i18next";
import { z } from "zod";

import InputError from "@/components/InputError";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Spinner } from "@/components/ui/spinner";
import { useAuthStore } from "@/store/authStore";

import { useResendValidationCode } from "../hooks/useResendValidationCode";
import { useValidateCode } from "../hooks/useValidateCode";

const validateCodeSchema = z.object({
  code: z
    .string()
    .length(6, "auth.validate_code.errors.code_invalid")
    .regex(/^\d+$/, "auth.validate_code.errors.code_invalid"),
});

type ValidateCodeFormValues = z.infer<typeof validateCodeSchema>;

const validateCodeResolver: Resolver<ValidateCodeFormValues> = async (values) => {
  const result = validateCodeSchema.safeParse(values);

  if (result.success) {
    return {
      values: result.data,
      errors: {},
    };
  }

  const fieldErrors =
    result.error.issues.reduce<FieldErrors<ValidateCodeFormValues>>(
      (accumulator, issue) => {
        const fieldName = issue.path[0];

        if (typeof fieldName !== "string") {
          return accumulator;
        }

        accumulator[fieldName as keyof ValidateCodeFormValues] = {
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

export function ValidateCodeForm() {
  const { t } = useTranslation();
  const pendingVerification = useAuthStore((state) => state.pendingVerification);
  const clearPendingVerification = useAuthStore(
    (state) => state.clearPendingVerification,
  );
  const validateCode = useValidateCode();
  const resendValidationCode = useResendValidationCode();
  const form = useForm<ValidateCodeFormValues>({
    resolver: validateCodeResolver,
    defaultValues: {
      code: "",
    },
  });
  const inputRefs = useRef<Array<HTMLInputElement | null>>([]);
  const codeValue =
    useWatch({
      control: form.control,
      name: "code",
    }) ?? "";
  const digits = Array.from({ length: 6 }, (_, index) => codeValue[index] ?? "");
  const codeError = form.formState.errors.code?.message;
  const rootError = form.formState.errors.root?.message;

  if (!pendingVerification) {
    return null;
  }

  const verification = pendingVerification;

  function setCode(nextCode: string): void {
    form.setValue("code", nextCode, {
      shouldDirty: true,
      shouldTouch: true,
      shouldValidate: true,
    });
  }

  function focusInput(index: number): void {
    inputRefs.current[index]?.focus();
    inputRefs.current[index]?.select();
  }

  function handleDigitChange(index: number, rawValue: string): void {
    const sanitized = rawValue.replace(/\D/g, "");

    if (sanitized.length === 0) {
      const nextDigits = [...digits];
      nextDigits[index] = "";
      setCode(nextDigits.join(""));
      return;
    }

    if (sanitized.length > 1) {
      handlePasteValue(sanitized);
      return;
    }

    const nextDigits = [...digits];
    nextDigits[index] = sanitized;
    setCode(nextDigits.join(""));

    if (index < 5) {
      focusInput(index + 1);
    }
  }

  function handlePasteValue(rawValue: string): void {
    const sanitized = rawValue.replace(/\D/g, "").slice(0, 6);

    if (!sanitized) {
      return;
    }

    const nextDigits = Array.from({ length: 6 }, (_, index) => sanitized[index] ?? "");
    setCode(nextDigits.join(""));

    const nextIndex = Math.min(sanitized.length, 6) - 1;
    focusInput(Math.max(nextIndex, 0));
  }

  function onSubmit(values: ValidateCodeFormValues): void {
    form.clearErrors("root");

    validateCode.mutate(
      {
        email: verification.email,
        code: values.code,
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

              if (Array.isArray(codeErrors) && typeof codeErrors[0] === "string") {
                form.setError("code", {
                  type: "server",
                  message: codeErrors[0],
                });
                return;
              }
            }
          }

          form.setError("root", {
            type: "server",
            message: "auth.validate_code.errors.unexpected",
          });
        },
      },
    );
  }

  return (
    <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-5" noValidate>
      <div className="rounded-2xl border border-primary/10 bg-primary/5 px-4 py-3 text-sm text-muted-foreground">
        <p className="font-medium text-foreground">{pendingVerification.message}</p>
        <p className="mt-1">
          {t("auth.validate_code.sent_to", {
            email: verification.email,
          })}
        </p>
        <p className="mt-1">
          {t("auth.validate_code.expires_at", {
            value: new Date(verification.verificationExpiresAt).toLocaleString(),
          })}
        </p>
        {resendValidationCode.isSuccess ? (
          <p className="mt-2 text-primary">{resendValidationCode.data.message}</p>
        ) : null}
      </div>

      {rootError ? (
        <div className="rounded-2xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
          {getErrorMessage(
            rootError,
            "auth.validate_code.errors.unexpected",
            t as (key: string) => string,
          )}
        </div>
      ) : null}

      <div className="space-y-2">
        <label className="text-sm font-medium tracking-[0.01em] text-foreground">
          {t("auth.validate_code.code")}
        </label>
        <div className="flex items-center gap-3">
          <div className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-secondary text-muted-foreground">
            <ShieldCheck className="size-5" />
          </div>
          <div className="grid flex-1 grid-cols-6 gap-2 sm:gap-3">
            {digits.map((digit, index) => (
              <Input
                key={index}
                ref={(element) => {
                  inputRefs.current[index] = element;
                }}
                value={digit}
                inputMode="numeric"
                autoComplete={index === 0 ? "one-time-code" : "off"}
                maxLength={1}
                aria-label={`${t("auth.validate_code.code")} ${index + 1}`}
                aria-invalid={codeError ? "true" : "false"}
                className="h-14 rounded-2xl border-border/70 bg-card px-0 text-center text-xl font-semibold shadow-[0_1px_2px_hsl(var(--foreground)/0.04)]"
                onChange={(event) => {
                  form.clearErrors("code");
                  handleDigitChange(index, event.target.value);
                }}
                onKeyDown={(event) => {
                  if (event.key === "Backspace" && !digits[index] && index > 0) {
                    const nextDigits = [...digits];
                    nextDigits[index - 1] = "";
                    setCode(nextDigits.join(""));
                    focusInput(index - 1);
                  }

                  if (event.key === "ArrowLeft" && index > 0) {
                    event.preventDefault();
                    focusInput(index - 1);
                  }

                  if (event.key === "ArrowRight" && index < 5) {
                    event.preventDefault();
                    focusInput(index + 1);
                  }
                }}
                onPaste={(event) => {
                  event.preventDefault();
                  form.clearErrors("code");
                  handlePasteValue(event.clipboardData.getData("text"));
                }}
              />
            ))}
          </div>
        </div>
        <InputError
          message={
            codeError
              ? getErrorMessage(
                  codeError,
                  "auth.validate_code.errors.code_invalid",
                  t as (key: string) => string,
                )
              : undefined
          }
        />
      </div>

      <div className="flex flex-col gap-3 sm:flex-row">
        <Button
          type="submit"
          size="lg"
          className="h-14 flex-1 rounded-2xl text-base font-semibold"
          disabled={validateCode.isPending}
        >
          {validateCode.isPending ? <Spinner className="size-5" /> : null}
          {validateCode.isPending
            ? t("common.loading")
            : t("auth.validate_code.submit")}
        </Button>
        <Button
          type="button"
          variant="outline"
          size="lg"
          className="h-14 rounded-2xl"
          onClick={() => {
            clearPendingVerification();
            form.reset();
          }}
        >
          {t("auth.validate_code.cancel")}
        </Button>
      </div>

      <Button
        type="button"
        variant="ghost"
        className="w-full"
        disabled={resendValidationCode.isPending}
        onClick={() => {
          resendValidationCode.reset();
          void resendValidationCode.mutateAsync({
            email: verification.email,
          });
        }}
      >
        {resendValidationCode.isPending ? <Spinner className="size-4" /> : null}
        {t("auth.validate_code.resend")}
      </Button>
    </form>
  );
}
