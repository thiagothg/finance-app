import { Link } from "@tanstack/react-router";
import { Landmark } from "lucide-react";
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from "@/components/ui/sidebar";
import { PATHS } from "@/config/paths";

export function AppTitle() {
  const { setOpenMobile } = useSidebar();
  return (
    <SidebarMenu>
      <SidebarMenuItem>
        <SidebarMenuButton
          size="lg"
          className="gap-0 py-0 hover:bg-transparent active:bg-transparent"
          asChild
        >
          <div className="">
            <Link
              to={PATHS.dashboard}
              onClick={() => setOpenMobile(false)}
              className="flex flex-1 items-center gap-2 text-start text-sm leading-tight group-data-[state=collapsed]:justify-center"
            >
              <Landmark className="size-6" />
              <div className="flex flex-col group-data-[state=collapsed]:hidden">
                <span className="truncate font-bold">FinTrack</span>
                <span className="truncate text-xs">
                  Personal Finance Tracker
                </span>
              </div>
            </Link>
          </div>
        </SidebarMenuButton>
      </SidebarMenuItem>
    </SidebarMenu>
  );
}
