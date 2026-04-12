import { type SVGProps } from "react";
import { Root as Radio, Item } from "@radix-ui/react-radio-group";
import { CircleCheck, RotateCcw, Settings } from "lucide-react";
import { cn } from "@/lib/utils";
import { useDirection } from "@/context/direction-provider";
import { type Collapsible, useLayout } from "@/context/layout-provider";
import { useTheme } from "@/context/theme-provider";
import { Button } from "@/components/ui/button";
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { useSidebar } from "./ui/sidebar";

// ─── Types ────────────────────────────────────────────────────────────────────

type RadioOption = {
  value: string;
  label: string;
  // opcional até os ícones customizados serem criados em assets/custom/
  icon?: (props: SVGProps<SVGSVGElement>) => React.ReactElement;
};

export function ConfigDrawer() {
  const { setOpen } = useSidebar();
  const { resetDir } = useDirection();
  const { resetTheme } = useTheme();
  const { resetLayout } = useLayout();

  const handleReset = () => {
    setOpen(true);
    resetDir();
    resetTheme();
    resetLayout();
  };

  return (
    <Sheet>
      <SheetTrigger asChild>
        <Button
          size="icon"
          variant="ghost"
          aria-label="Open theme settings"
          aria-describedby="config-drawer-description"
          className="rounded-full"
        >
          <Settings aria-hidden="true" />
        </Button>
      </SheetTrigger>
      <SheetContent className="flex flex-col">
        <SheetHeader className="pb-0 text-start">
          <SheetTitle>Theme Settings</SheetTitle>
          <SheetDescription id="config-drawer-description">
            Adjust the appearance and layout to suit your preferences.
          </SheetDescription>
        </SheetHeader>
        <div className="space-y-6 overflow-y-auto px-4">
          <ThemeConfig />
          <SidebarConfig />
          <LayoutConfig />
          <DirConfig />
        </div>
        <SheetFooter className="gap-2">
          <Button
            variant="destructive"
            onClick={handleReset}
            aria-label="Reset all settings to default values"
          >
            Reset
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}

// ─── SectionTitle ─────────────────────────────────────────────────────────────

function SectionTitle({
  title,
  showReset = false,
  onReset,
  className,
}: {
  title: string;
  showReset?: boolean;
  onReset?: () => void;
  className?: string;
}) {
  return (
    <div
      className={cn(
        "mb-2 flex items-center gap-2 text-sm font-semibold text-muted-foreground",
        className
      )}
    >
      {title}
      {showReset && onReset && (
        <Button
          size="icon"
          variant="secondary"
          className="size-4 rounded-full"
          onClick={onReset}
          aria-label={`Reset ${title.toLowerCase()} to default`}
        >
          <RotateCcw className="size-3" />
        </Button>
      )}
    </div>
  );
}

// ─── RadioGroupItem ───────────────────────────────────────────────────────────

function RadioGroupItem({
  item,
  isTheme = false,
}: {
  item: RadioOption;
  isTheme?: boolean;
}) {
  return (
    <Item
      value={item.value}
      className={cn("group transition duration-200 ease-in outline-none")}
      aria-label={`Select ${item.label.toLowerCase()}`}
      aria-describedby={`${item.value}-description`}
    >
      <div
        className={cn(
          "relative rounded-[6px] ring-[1px] ring-border",
          "group-data-[state=checked]:shadow-2xl group-data-[state=checked]:ring-primary",
          "group-focus-visible:ring-2",
          // quando não há ícone, mostra um placeholder visual
          !item.icon && "flex h-12 items-center justify-center bg-muted/40 px-2"
        )}
        role="img"
        aria-hidden="false"
        aria-label={`${item.label} option preview`}
      >
        <CircleCheck
          className={cn(
            "size-6 fill-primary stroke-white",
            "group-data-[state=unchecked]:hidden",
            "absolute top-0 right-0 translate-x-1/2 -translate-y-1/2"
          )}
          aria-hidden="true"
        />
        {item.icon ? (
          <item.icon
            className={cn(
              !isTheme &&
                "fill-primary stroke-primary group-data-[state=unchecked]:fill-muted-foreground group-data-[state=unchecked]:stroke-muted-foreground"
            )}
            aria-hidden="true"
          />
        ) : (
          // placeholder até os ícones customizados estarem prontos
          <span className="text-xs text-muted-foreground">{item.label}</span>
        )}
      </div>
      <div
        className="mt-1 text-xs"
        id={`${item.value}-description`}
        aria-live="polite"
      >
        {item.label}
      </div>
    </Item>
  );
}

// ─── ThemeConfig ──────────────────────────────────────────────────────────────

const THEME_OPTIONS: RadioOption[] = [
  { value: "system", label: "System" },
  { value: "light", label: "Light" },
  { value: "dark", label: "Dark" },
];

function ThemeConfig() {
  const { defaultTheme, theme, setTheme } = useTheme();
  return (
    <div>
      <SectionTitle
        title="Theme"
        showReset={theme !== defaultTheme}
        onReset={() => setTheme(defaultTheme)}
      />
      <Radio
        value={theme}
        onValueChange={setTheme}
        className="grid w-full max-w-md grid-cols-3 gap-4"
        aria-label="Select theme preference"
      >
        {THEME_OPTIONS.map((item) => (
          <RadioGroupItem key={item.value} item={item} isTheme />
        ))}
      </Radio>
      <div className="sr-only">
        Choose between system preference, light mode, or dark mode
      </div>
    </div>
  );
}

// ─── SidebarConfig ────────────────────────────────────────────────────────────

const SIDEBAR_OPTIONS: RadioOption[] = [
  { value: "inset", label: "Inset" },
  { value: "floating", label: "Floating" },
  { value: "sidebar", label: "Sidebar" },
];

function SidebarConfig() {
  const { defaultVariant, variant, setVariant } = useLayout();
  return (
    <div className="max-md:hidden">
      <SectionTitle
        title="Sidebar"
        showReset={defaultVariant !== variant}
        onReset={() => setVariant(defaultVariant)}
      />
      <Radio
        value={variant}
        onValueChange={setVariant}
        className="grid w-full max-w-md grid-cols-3 gap-4"
        aria-label="Select sidebar style"
      >
        {SIDEBAR_OPTIONS.map((item) => (
          <RadioGroupItem key={item.value} item={item} />
        ))}
      </Radio>
      <div className="sr-only">
        Choose between inset, floating, or standard sidebar layout
      </div>
    </div>
  );
}

// ─── LayoutConfig ─────────────────────────────────────────────────────────────

const LAYOUT_OPTIONS: RadioOption[] = [
  { value: "default", label: "Default" },
  { value: "icon", label: "Compact" },
  { value: "offcanvas", label: "Full layout" },
];

function LayoutConfig() {
  const { open, setOpen } = useSidebar();
  const { defaultCollapsible, collapsible, setCollapsible } = useLayout();

  const radioState = open ? "default" : collapsible;

  return (
    <div className="max-md:hidden">
      <SectionTitle
        title="Layout"
        showReset={radioState !== "default"}
        onReset={() => {
          setOpen(true);
          setCollapsible(defaultCollapsible);
        }}
      />
      <Radio
        value={radioState}
        onValueChange={(v) => {
          if (v === "default") {
            setOpen(true);
            return;
          }
          setOpen(false);
          setCollapsible(v as Collapsible);
        }}
        className="grid w-full max-w-md grid-cols-3 gap-4"
        aria-label="Select layout style"
      >
        {LAYOUT_OPTIONS.map((item) => (
          <RadioGroupItem key={item.value} item={item} />
        ))}
      </Radio>
      <div className="sr-only">
        Choose between default expanded, compact icon-only, or full layout mode
      </div>
    </div>
  );
}

// ─── DirConfig ────────────────────────────────────────────────────────────────
// TODO: substituir span placeholder pelos ícones IconDir quando criados
//       em assets/custom/icon-dir.tsx

const DIR_OPTIONS: RadioOption[] = [
  { value: "ltr", label: "Left to Right" },
  { value: "rtl", label: "Right to Left" },
];

function DirConfig() {
  const { defaultDir, dir, setDir } = useDirection();
  return (
    <div>
      <SectionTitle
        title="Direction"
        showReset={defaultDir !== dir}
        onReset={() => setDir(defaultDir)}
      />
      <Radio
        value={dir}
        onValueChange={setDir}
        className="grid w-full max-w-md grid-cols-2 gap-4"
        aria-label="Select site direction"
      >
        {DIR_OPTIONS.map((item) => (
          <RadioGroupItem key={item.value} item={item} />
        ))}
      </Radio>
      <div className="sr-only">
        Choose between left-to-right or right-to-left site direction
      </div>
    </div>
  );
}
