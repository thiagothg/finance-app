import { Outlet } from "@tanstack/react-router";
import { Bell, Palette, UserCog } from "lucide-react";
import { Separator } from "@/components/ui/separator";
import { ConfigDrawer } from "@/components/ConfigDrawer";
import { ProfileDropdown } from "@/components/ProfileDropdown";
import { Search } from "@/components/Search";
import { ThemeSwitch } from "@/components/ThemeSwitch";
import { Header } from "@/components/layout/Header";
import { Main } from "@/components/layout/Main";
import { SidebarNav } from "./components/SidebarNav";
import { PATHS } from "@/config/paths";

const sidebarNavItems = [
  {
    title: "Profile",
    href: PATHS.settings.profile,
    icon: <UserCog size={18} />,
  },
  {
    title: "Appearance",
    href: PATHS.settings.appearance,
    icon: <Palette size={18} />,
  },
  {
    title: "Notifications",
    href: PATHS.settings.notifications,
    icon: <Bell size={18} />,
  },
];

export function SettingsPage() {
  return (
    <>
      {/* ===== Top Heading ===== */}
      <Header>
        <Search />
        <div className="ms-auto flex items-center space-x-4">
          <ThemeSwitch />
          <ConfigDrawer />
          <ProfileDropdown />
        </div>
      </Header>

      <Main fixed>
        <div className="space-y-0.5">
          <h1 className="text-2xl font-bold tracking-tight md:text-3xl">
            Settings
          </h1>
          <p className="text-muted-foreground">
            Manage your account settings and set e-mail preferences.
          </p>
        </div>
        <Separator className="my-4 lg:my-6" />
        <div className="flex flex-1 flex-col space-y-2 overflow-hidden md:space-y-2 lg:flex-row lg:space-y-0 lg:space-x-12">
          <aside className="top-0 lg:sticky lg:w-1/5">
            <SidebarNav items={sidebarNavItems} />
          </aside>
          <div className="flex w-full overflow-y-hidden p-1">
            <Outlet />
          </div>
        </div>
      </Main>
    </>
  );
}
