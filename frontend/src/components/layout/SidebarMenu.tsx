import {
  LayoutDashboard,
  Monitor,
  Bell,
  Palette,
  Settings,
  Wrench,
  UserCog,
  ShieldCheck,
  Tags,
  Target,
  ArrowLeftRight,
} from "lucide-react";
import { useAuthStore } from "@/stores/authStore";
import { type SidebarData } from "./nav-types.ts";
import { PATHS } from "@/config/paths.ts";

const user = useAuthStore.getState().user;

export const sidebarData: SidebarData = {
  user: {
    id: user?.id ?? "",
    name: user?.name ?? "User",
    email: user?.email ?? "",
    avatar: user?.avatar ?? "/avatars/shadcn.jpg",
    createdAt: user?.createdAt,
  },
  teams: [],
  navGroups: [
    {
      title: "General",
      items: [
        {
          title: "Dashboard",
          url: PATHS.dashboard,
          icon: LayoutDashboard,
        },
        {
          title: "transactions",
          url: PATHS.transactions,
          icon: ArrowLeftRight,
        },
        {
          title: "Goals",
          url: PATHS.goals,
          icon: Target,
        },
      ],
    },
    {
      title: "Management",
      items: [
        {
          title: "Accounts",
          url: PATHS.management.accounts,
          icon: ShieldCheck,
        },
        {
          title: "categories",
          url: PATHS.management.categories,
          icon: Tags,
        },
      ],
    },
    {
      title: "Other",
      items: [
        {
          title: "Settings",
          icon: Settings,
          items: [
            {
              title: "Profile",
              url: PATHS.settings.profile,
              icon: UserCog,
            },
            {
              title: "Account",
              url: PATHS.settings.account,
              icon: Wrench,
            },
            {
              title: "Appearance",
              url: PATHS.settings.appearance,
              icon: Palette,
            },
            {
              title: "Notifications",
              url: PATHS.settings.notifications,
              icon: Bell,
            },
            {
              title: "Display",
              url: PATHS.settings.display,
              icon: Monitor,
            },
          ],
        },
      ],
    },
  ],
};
