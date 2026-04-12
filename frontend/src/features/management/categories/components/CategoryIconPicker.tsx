import { useMemo, useState } from "react";
import { icons } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { cn } from "@/lib/utils";

interface CategoryIconPickerProps {
  currentIcon: string;
  onSelectIcon: (iconName: string) => void;
}

const featuredIcons = [
  "Home",
  "Utensils",
  "Car",
  "Briefcase",
  "Wallet",
  "Heart",
  "ShoppingBag",
  "Landmark",
];

function IconButton({
  iconName,
  isSelected,
  onClick,
}: {
  iconName: string;
  isSelected: boolean;
  onClick: () => void;
}) {
  const Icon = icons[iconName as keyof typeof icons] ?? icons.Circle;

  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        "flex size-11 items-center justify-center rounded-2xl border bg-background transition-all",
        isSelected
          ? "border-primary bg-primary/10 text-primary shadow-sm"
          : "border-border text-muted-foreground hover:border-primary/40 hover:text-foreground",
      )}
      aria-pressed={isSelected}
      aria-label={iconName}
    >
      <Icon className="size-4" />
    </button>
  );
}

export function CategoryIconPicker({
  currentIcon,
  onSelectIcon,
}: CategoryIconPickerProps) {
  const [isBrowserOpen, setIsBrowserOpen] = useState(false);

  const displayedIcons = useMemo(() => {
    if (currentIcon && !featuredIcons.includes(currentIcon)) {
      return [currentIcon, ...featuredIcons.slice(0, featuredIcons.length - 1)];
    }
    return featuredIcons;
  }, [currentIcon]);

  const allIcons = useMemo(
    () =>
      Object.keys(icons)
        .filter((iconName) => /^[A-Za-z0-9]+$/.test(iconName))
        .sort((left, right) => left.localeCompare(right)),
    [],
  );

  return (
    <>
      <div className="space-y-3">
        <div className="flex flex-wrap gap-2">
          {displayedIcons.map((iconName) => (
            <IconButton
              key={iconName}
              iconName={iconName}
              isSelected={currentIcon === iconName}
              onClick={() => onSelectIcon(iconName)}
            />
          ))}
        </div>

        <Button
          type="button"
          variant="outline"
          className="rounded-full"
          onClick={() => setIsBrowserOpen(true)}
        >
          More icons
        </Button>
      </div>

      <Dialog open={isBrowserOpen} onOpenChange={setIsBrowserOpen}>
        <DialogContent className="max-w-3xl">
          <DialogHeader>
            <DialogTitle>Choose an icon</DialogTitle>
          </DialogHeader>

          <div className="max-h-[60vh] overflow-y-auto pr-1">
            <div className="grid grid-cols-5 gap-2 sm:grid-cols-6 md:grid-cols-8">
              {allIcons.map((iconName) => (
                <IconButton
                  key={iconName}
                  iconName={iconName}
                  isSelected={currentIcon === iconName}
                  onClick={() => {
                    onSelectIcon(iconName);
                    setIsBrowserOpen(false);
                  }}
                />
              ))}
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
}
