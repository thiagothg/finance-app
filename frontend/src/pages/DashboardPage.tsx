import {
  ArrowUpRight,
  CreditCard,
  PiggyBank,
  ShieldCheck,
  WalletCards,
} from "lucide-react";
import { useTranslation } from "react-i18next";

import { useAuthStore } from "@/store/authStore";

const statCards = [
  {
    icon: WalletCards,
    valueKey: "dashboard.stats.cash_value",
    labelKey: "dashboard.stats.cash_label",
  },
  {
    icon: PiggyBank,
    valueKey: "dashboard.stats.savings_value",
    labelKey: "dashboard.stats.savings_label",
  },
  {
    icon: ShieldCheck,
    valueKey: "dashboard.stats.security_value",
    labelKey: "dashboard.stats.security_label",
  },
] as const;

export default function DashboardPage(): React.JSX.Element {
  const { t } = useTranslation();
  const user = useAuthStore((state) => state.user);

  return (
    <div className="flex w-full flex-col gap-6">
      <section className="rounded-[2rem] border border-border/60 bg-[radial-gradient(circle_at_top_right,_hsl(var(--primary)/0.16),_transparent_33%),linear-gradient(180deg,_hsl(var(--card)/0.96)_0%,_hsl(var(--card))_100%)] p-6 shadow-[0_24px_70px_-45px_hsl(var(--foreground)/0.35)] backdrop-blur sm:p-8">
        <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div className="max-w-2xl space-y-3">
            <div className="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-sm font-medium text-primary">
              <ShieldCheck className="size-4" />
              {t("dashboard.badge")}
            </div>
            <h2 className="text-3xl font-semibold tracking-[-0.03em] text-foreground sm:text-4xl">
              {t("dashboard.title", {
                name: user?.name ?? user?.email ?? "User",
              })}
            </h2>
            <p className="text-base leading-7 text-muted-foreground">
              {t("dashboard.subtitle")}
            </p>
          </div>

          <div className="grid gap-3 sm:grid-cols-3 lg:min-w-[420px]">
            {statCards.map((card) => {
              const Icon = card.icon;

              return (
                <article
                  key={card.labelKey}
                  className="rounded-[1.5rem] border border-border/60 bg-background/78 p-4 backdrop-blur"
                >
                  <div className="flex items-center justify-between gap-3">
                    <div className="flex size-11 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                      <Icon className="size-5" />
                    </div>
                    <ArrowUpRight className="size-4 text-muted-foreground" />
                  </div>
                  <p className="mt-5 text-2xl font-semibold tracking-[-0.03em] text-foreground">
                    {t(card.valueKey)}
                  </p>
                  <p className="mt-1 text-sm text-muted-foreground">
                    {t(card.labelKey)}
                  </p>
                </article>
              );
            })}
          </div>
        </div>
      </section>

      <section className="grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.9fr)]">
        <article className="rounded-[1.75rem] border border-border/60 bg-card/92 p-6 shadow-sm backdrop-blur">
          <div className="mb-4 flex items-center gap-3">
            <div className="flex size-11 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
              <CreditCard className="size-5" />
            </div>
            <div>
              <h2 className="text-xl font-semibold text-foreground">
                {t("dashboard.overview_title")}
              </h2>
              <p className="text-sm text-muted-foreground">
                {t("dashboard.overview_subtitle")}
              </p>
            </div>
          </div>
          <div className="grid gap-4 md:grid-cols-2">
            <div className="rounded-[1.5rem] bg-muted/60 p-5 text-sm leading-7 text-muted-foreground">
              {t("dashboard.placeholder")}
            </div>
            <div className="rounded-[1.5rem] border border-dashed border-border bg-background/80 p-5">
              <p className="text-sm font-medium text-foreground">
                {t("dashboard.next_steps_title")}
              </p>
              <p className="mt-2 text-sm leading-7 text-muted-foreground">
                {t("dashboard.next_steps_copy")}
              </p>
            </div>
          </div>
        </article>

        <aside className="rounded-[1.75rem] border border-border/60 bg-card/92 p-6 shadow-sm backdrop-blur">
          <h2 className="text-lg font-semibold text-foreground">
            {t("dashboard.account_title")}
          </h2>
          <dl className="mt-5 space-y-4 text-sm">
            <div>
              <dt className="text-muted-foreground">{t("dashboard.name")}</dt>
              <dd className="mt-1 font-medium text-foreground">
                {user?.name ?? "-"}
              </dd>
            </div>
            <div>
              <dt className="text-muted-foreground">{t("dashboard.email")}</dt>
              <dd className="mt-1 font-medium text-foreground">
                {user?.email ?? "-"}
              </dd>
            </div>
          </dl>

          <div className="mt-6 rounded-[1.5rem] bg-secondary/60 p-5">
            <p className="text-sm font-medium text-foreground">
              {t("dashboard.security_title")}
            </p>
            <p className="mt-2 text-sm leading-7 text-muted-foreground">
              {t("dashboard.security_copy")}
            </p>
          </div>
        </aside>
      </section>
    </div>
  );
}
