import { useMemo } from "react";
import { cn } from "@/lib/utils";
import { Spinner } from "@/components/ui/spinner";
import { ConfigDrawer } from "@/components/ConfigDrawer";
import { ProfileDropdown } from "@/components/ProfileDropdown";
import ScreenLayout from "@/components/ScreenLayout";
import { Search } from "@/components/Search";
import { ThemeSwitch } from "@/components/ThemeSwitch";
import WealthCard from "@/components/WealthCard";
import { Header } from "@/components/layout/Header";
import { AccountCard } from "./components/AccountCard";
import { AddAccountForm } from "./components/AddAccountForm";
import { useAccounts } from "./hooks/useAccounts";

function AccountsPage() {
  const { data: accountsResponse, isLoading } = useAccounts();

  const { balance, liabilities } = useMemo(() => {
    if (!accountsResponse) {
      return { balance: 0, liabilities: 0 };
    }

    return { balance: accountsResponse.meta.total_balance, liabilities: 0 };
  }, [accountsResponse]);

  return (
    <>
      <Header fixed>
        <Search />
        <div className="ms-auto flex items-center space-x-4">
          <ThemeSwitch />
          <ConfigDrawer />
          <ProfileDropdown />
        </div>
      </Header>

      <ScreenLayout title="Accounts" rightAction={<AddAccountForm />}>
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <WealthCard>
              <div className="p-4">
                <div className="text-sm text-muted-foreground">Balance</div>
                <div className="text-2xl font-semibold">
                  {new Intl.NumberFormat("en-US", {
                    style: "currency",
                    currency: "USD",
                  }).format(balance)}
                </div>
              </div>
            </WealthCard>
            <WealthCard>
              <div className="p-4">
                <div className="text-sm text-muted-foreground">Liabilities</div>
                <div
                  className={cn("text-2xl font-semibold", "text-destructive")}
                >
                  {new Intl.NumberFormat("en-US", {
                    style: "currency",
                    currency: "USD",
                  }).format(liabilities)}
                </div>
              </div>
            </WealthCard>
          </div>

          {isLoading && (
            <div className="flex justify-center">
              <Spinner />
            </div>
          )}

          <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            {accountsResponse?.data.map((account) => (
              <AccountCard key={account.id} account={account} />
            ))}
          </div>
        </div>
      </ScreenLayout>
    </>
  );
}

export default AccountsPage;
