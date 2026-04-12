import { icons, type LucideProps } from "lucide-react";

import { cn } from "@/lib/utils";

interface CategoryIconProps {
  icon: string;
  color: string;
  size?: "sm" | "md" | "lg";
}

export function CategoryIcon({
  icon,
  color,
  size = "md",
}: CategoryIconProps) {
  const LucideIcon = icons[icon as keyof typeof icons] ?? icons.Circle;

  const sizeClasses = {
    sm: "size-6",
    md: "size-8",
    lg: "size-10",
  };

  const iconSize: Record<typeof size, LucideProps["size"]> = {
    sm: 14,
    md: 18,
    lg: 22,
  };

  return (
    <div
      className={cn(
        "flex items-center justify-center rounded-full",
        sizeClasses[size],
      )}
      style={{ backgroundColor: color }}
    >
      <LucideIcon
        className="text-white"
        size={iconSize[size]}
        strokeWidth={2.5}
      />
    </div>
  );
}
