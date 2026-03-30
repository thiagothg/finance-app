import { Moon, SunMedium } from "lucide-react";
import { useTranslation } from "react-i18next";

import { Button } from "@/components/ui/button";
import { useTheme } from "@/hooks/useTheme";

interface ThemeToggleProps {
  className?: string;
}

export function ThemeToggle({
  className,
}: ThemeToggleProps): React.JSX.Element {
  const { t } = useTranslation();
  const { theme, toggleTheme } = useTheme();
  const isDark = theme === "dark";

  return (
    <Button
      type="button"
      variant="ghost"
      size="icon"
      className={`border border-border/70 bg-card/88 text-foreground shadow-sm backdrop-blur hover:bg-accent hover:text-accent-foreground dark:border-border/80 dark:bg-card/80 ${className ?? ""}`}
      onClick={toggleTheme}
      aria-label={t("theme.toggle")}
      title={t("theme.toggle")}
    >
      {isDark ? (
        <SunMedium className="size-4.5 text-amber-400" />
      ) : (
        <Moon className="size-4.5 text-emerald-700 dark:text-emerald-300" />
      )}
    </Button>
  );
}
