import { Landmark } from "lucide-react";

import { cn } from "@/lib/utils";

interface AppLogoProps {
  compact?: boolean;
  className?: string;
}

export function AppLogo({
  compact = false,
  className,
}: AppLogoProps): React.JSX.Element {
  return (
    <div className={cn("flex items-center gap-3", className)}>
      <div className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg shadow-primary/20">
        <Landmark className="size-6" />
      </div>

      {compact ? null : (
        <div className="min-w-0">
          <p className="text-base font-semibold tracking-[-0.02em] text-foreground">
            Finance App
          </p>
        </div>
      )}
    </div>
  );
}
