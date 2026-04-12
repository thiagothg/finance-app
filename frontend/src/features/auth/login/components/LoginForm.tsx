import { z } from "zod";
import axios from "axios";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Link } from "@tanstack/react-router";
import { Loader2, LogIn } from "lucide-react";
import { useTranslation } from "react-i18next";
import { IconFacebook, IconGithub } from "@/assets/brand-icons";
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
import { Input } from "@/components/ui/input";
import { PasswordInput } from "@/components/PasswordInput";
import { useLogin } from "../hooks/useLogin";
import { PATHS } from "@/config/paths";

const formSchema = z.object({
  email: z.email({
    error: (iss) => (iss.input === "" ? "Please enter your email" : undefined),
  }),
  password: z
    .string()
    .min(1, "Please enter your password")
    .min(8, "Password must be at least 8 characters long"),
});

interface UserAuthFormProps extends React.HTMLAttributes<HTMLFormElement> {
  redirectTo?: string;
}

export function LoginForm({ className, ...props }: UserAuthFormProps) {
  const { t } = useTranslation();
  const login = useLogin();

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      email: "",
      password: "",
    },
  });

  const isLoading = login.isPending;
  const rootError = form.formState.errors.root?.message;

  function onSubmit(data: z.infer<typeof formSchema>) {
    form.clearErrors("root");

    login.mutate(data, {
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
                    typeof firstMessage === "string" && firstMessage.length > 0
                      ? firstMessage
                      : `Invalid ${field}`,
                });
              }
            });

            return;
          }
        }

        if (axios.isAxiosError(error) && error.response?.status === 401) {
          form.setError("root", {
            type: "server",
            message: "Invalid email or password.",
          });
          return;
        }

        if (axios.isAxiosError(error) && error.response?.status === 429) {
          form.setError("root", {
            type: "server",
            message: "Too many attempts. Please try again later.",
          });
          return;
        }

        form.setError("root", {
          type: "server",
          message: "Something went wrong. Please try again.",
        });
      },
    });
  }

  return (
    <Form {...form}>
      <form
        onSubmit={form.handleSubmit(onSubmit)}
        className={cn("grid gap-3", className)}
        {...props}
      >
        {rootError ? (
          <div className="rounded-2xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
            {rootError}
          </div>
        ) : null}

        <FormField
          control={form.control}
          name="email"
          render={({ field }) => (
            <FormItem>
              <FormLabel>{t("auth.login.email")}</FormLabel>
              <FormControl>
                <Input placeholder="name@example.com" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name="password"
          render={({ field }) => (
            <FormItem className="relative">
              <FormLabel>{t("auth.login.password")}</FormLabel>
              <FormControl>
                <PasswordInput placeholder="********" {...field} />
              </FormControl>
              <FormMessage />
              <Link
                 to={PATHS.auth.forgotPassword}
                className="absolute end-0 -top-0.5 text-sm font-medium text-muted-foreground hover:opacity-75"
              >
                Forgot password?
              </Link>
            </FormItem>
          )}
        />
        <Button className="mt-2" disabled={isLoading}>
          {isLoading ? <Loader2 className="animate-spin" /> : <LogIn />}
          Sign in
        </Button>

        <div className="relative my-2">
          <div className="absolute inset-0 flex items-center">
            <span className="w-full border-t" />
          </div>
          <div className="relative flex justify-center text-xs uppercase">
            <span className="bg-background px-2 text-muted-foreground">
              Or continue with
            </span>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-2">
          <Button variant="outline" type="button" disabled={isLoading}>
            <IconGithub className="h-4 w-4" /> GitHub
          </Button>
          <Button variant="outline" type="button" disabled={isLoading}>
            <IconFacebook className="h-4 w-4" /> Facebook
          </Button>
        </div>
      </form>
    </Form>
  );
}
