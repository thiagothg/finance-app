import * as Collapsible from "@radix-ui/react-collapsible";
import {
  ChevronRight,
  FolderKanban,
  LogOut,
  ShieldCheck,
  User2,
  LayoutDashboard,
  Tags,
  type LucideIcon,
} from "lucide-react";
import { useTranslation } from "react-i18next";
import { NavLink, useLocation } from "react-router-dom";

import { AppLogo } from "@/components/AppLogo";
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from "@/components/ui/sidebar";
import { cn } from "@/lib/utils";
import { useAuthStore } from "@/store/authStore";

interface SidebarSubItem {
  labelKey: string;
  to: string;
}

interface SidebarItem {
  icon: LucideIcon;
  labelKey: string;
  to?: string;
  items?: readonly SidebarSubItem[];
  defaultOpen?: boolean;
}

interface SidebarSection {
  labelKey: string;
  items: readonly SidebarItem[];
}

const sidebarSections = [
  {
    labelKey: "app_shell.navigation.main_menu",
    items: [
      {
        to: "/dashboard",
        icon: LayoutDashboard,
        labelKey: "app_shell.navigation.dashboard",
      },
      {
        to: "/categories",
        icon: Tags,
        labelKey: "categories.title",
      },
      {
        icon: FolderKanban,
        labelKey: "app_shell.navigation.planning",
        defaultOpen: true,
        items: [
          {
            to: "/dashboard#overview",
            labelKey: "app_shell.navigation.overview",
          },
          {
            to: "/dashboard#security",
            labelKey: "app_shell.navigation.security",
          },
        ],
      },
    ],
  },
] satisfies readonly SidebarSection[];

function isRouteActive(
  currentPathname: string,
  currentHash: string,
  target: string,
): boolean {
  const [pathname, hash] = target.split("#");

  if (currentPathname !== pathname) {
    return false;
  }

  if (!hash) {
    return true;
  }

  return currentHash === `#${hash}`;
}

function isItemActive(
  currentPathname: string,
  currentHash: string,
  item: SidebarItem,
): boolean {
  if (item.to) {
    return isRouteActive(currentPathname, currentHash, item.to);
  }

  return (
    item.items?.some((subItem) =>
      isRouteActive(currentPathname, currentHash, subItem.to),
    ) ?? false
  );
}

export function AppSidebar(): React.JSX.Element {
  const { t } = useTranslation();
  const location = useLocation();
  const { open, isMobile, setOpenMobile } = useSidebar();
  const user = useAuthStore((state) => state.user);
  const clearAuth = useAuthStore((state) => state.clearAuth);
  const userLabel = user?.name ?? user?.email ?? "User";

  function handleItemClick(): void {
    if (isMobile) {
      setOpenMobile(false);
    }
  }

  return (
    <Sidebar collapsible="icon" variant="inset">
      <SidebarHeader>
        <div
          className={cn(
            "flex items-center justify-between gap-3",
            !open && "justify-center",
          )}
        >
          <AppLogo
            compact={!open}
            className={cn(
              !open && "justify-center",
              "[&_p]:text-sidebar-foreground [&_p:first-of-type]:text-sidebar-foreground/70",
            )}
          />
        </div>
      </SidebarHeader>

      <SidebarContent>
        {sidebarSections.map((section) => (
          <SidebarGroup key={section.labelKey} className="flex flex-col gap-3">
            <SidebarGroupLabel className={cn(!open && "sr-only")}>
              {t(section.labelKey)}
            </SidebarGroupLabel>

            <SidebarGroupContent>
              <SidebarMenu
                className={cn("flex flex-col gap-1", !open && "items-center")}
              >
                {section.items.map((item) => {
                  const Icon = item.icon;
                  const active = isItemActive(
                    location.pathname,
                    location.hash,
                    item,
                  );

                  if (!item.items?.length) {
                    return (
                      <SidebarMenuItem key={item.labelKey}>
                        <SidebarMenuButton
                          asChild
                          tooltip={t(item.labelKey)}
                          isActive={active}
                        >
                          <NavLink
                            to={item.to ?? "/dashboard"}
                            onClick={handleItemClick}
                          >
                            <Icon />
                            {open ? (
                              <span className="truncate">
                                {t(item.labelKey)}
                              </span>
                            ) : (
                              <span className="sr-only">
                                {t(item.labelKey)}
                              </span>
                            )}
                          </NavLink>
                        </SidebarMenuButton>
                      </SidebarMenuItem>
                    );
                  }

                  return (
                    <Collapsible.Root
                      key={item.labelKey}
                      defaultOpen={active || item.defaultOpen}
                      className="group/collapsible w-full"
                    >
                      <SidebarMenuItem className="relative w-full">
                        <Collapsible.Trigger asChild>
                          <SidebarMenuButton
                            tooltip={t(item.labelKey)}
                            isActive={active}
                            className={cn("w-full", open && "justify-between")}
                          >
                            <span
                              className={cn(
                                "flex items-center gap-3",
                                !open && "justify-center",
                              )}
                            >
                              <Icon />
                              {open ? (
                                <span className="truncate">
                                  {t(item.labelKey)}
                                </span>
                              ) : (
                                <span className="sr-only">
                                  {t(item.labelKey)}
                                </span>
                              )}
                            </span>
                            {open ? (
                              <ChevronRight className="transition-transform group-data-[state=open]/collapsible:rotate-90" />
                            ) : null}
                          </SidebarMenuButton>
                        </Collapsible.Trigger>

                        <Collapsible.Content
                          className={cn(
                            "overflow-hidden",
                            open
                              ? "pt-1"
                              : "absolute left-full top-0 z-20 ml-3 w-56 rounded-2xl border border-border/70 bg-background p-2 shadow-xl",
                          )}
                        >
                          <ul
                            className={cn(
                              "flex flex-col gap-1",
                              open && "border-l border-sidebar-border pl-4",
                            )}
                          >
                            {item.items.map((subItem) => {
                              const subItemActive = isRouteActive(
                                location.pathname,
                                location.hash,
                                subItem.to,
                              );

                              return (
                                <li key={subItem.labelKey}>
                                  <NavLink
                                    to={subItem.to}
                                    onClick={handleItemClick}
                                    className={cn(
                                      "flex min-h-10 items-center rounded-xl px-3 text-sm font-medium transition-colors",
                                      open
                                        ? "text-sidebar-foreground/72 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                                        : "text-foreground hover:bg-accent hover:text-accent-foreground",
                                      subItemActive &&
                                        (open
                                          ? "bg-sidebar-primary text-sidebar-primary-foreground"
                                          : "bg-accent text-accent-foreground"),
                                    )}
                                  >
                                    {t(subItem.labelKey)}
                                  </NavLink>
                                </li>
                              );
                            })}
                          </ul>
                        </Collapsible.Content>
                      </SidebarMenuItem>
                    </Collapsible.Root>
                  );
                })}
              </SidebarMenu>
            </SidebarGroupContent>
          </SidebarGroup>
        ))}
      </SidebarContent>

      <SidebarFooter>
        <SidebarMenu className="flex flex-col gap-1">
          <SidebarMenuItem>
            <SidebarMenuButton tooltip={userLabel}>
              <User2 />
              {open ? (
                <span className="truncate">{userLabel}</span>
              ) : (
                <span className="sr-only">{userLabel}</span>
              )}
            </SidebarMenuButton>
          </SidebarMenuItem>

          <SidebarMenuItem>
            <SidebarMenuButton tooltip={t("auth.logout")} onClick={clearAuth}>
              <LogOut />
              {open ? (
                <span className="truncate">{t("auth.logout")}</span>
              ) : (
                <span className="sr-only">{t("auth.logout")}</span>
              )}
            </SidebarMenuButton>
          </SidebarMenuItem>

          <SidebarMenuItem>
            <SidebarMenuButton tooltip={t("app_shell.secure_session")}>
              <ShieldCheck />
              {open ? (
                <span className="truncate">
                  {t("app_shell.secure_session")}
                </span>
              ) : (
                <span className="sr-only">{t("app_shell.secure_session")}</span>
              )}
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarFooter>
    </Sidebar>
  );
}
