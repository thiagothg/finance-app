const logoApiToken = import.meta.env.VITE_LOGO_API_TOKEN || "";
const logoApiUrl = logoApiToken ? `https://img.logo.dev` : "#";

function getBankLogoUrl(domain: string): string {
  return `${logoApiUrl}/${domain}?token=${logoApiToken}`;
}

export interface Bank {
  id: string;
  name: string;
  logo: string; // URL to logo
  color: string; // brand color for fallback
}

export const banks: Bank[] = [
  {
    id: "chase",
    name: "Chase",
    logo: getBankLogoUrl("chase.com"),
    color: "bg-blue-600",
  },
  {
    id: "bofa",
    name: "Bank of America",
    logo: getBankLogoUrl("bankofamerica.com"),
    color: "bg-red-600",
  },
  {
    id: "wells",
    name: "Wells Fargo",
    logo: getBankLogoUrl("wellsfargo.com"),
    color: "bg-yellow-600",
  },
  {
    id: "citi",
    name: "Citi",
    logo: getBankLogoUrl("citi.com"),
    color: "bg-blue-500",
  },
  {
    id: "capital_one",
    name: "Capital One",
    logo: getBankLogoUrl("capitalone.com"),
    color: "bg-red-500",
  },
  {
    id: "amex",
    name: "American Express",
    logo: getBankLogoUrl("americanexpress.com"),
    color: "bg-blue-700",
  },
  {
    id: "usbank",
    name: "US Bank",
    logo: getBankLogoUrl("usbank.com"),
    color: "bg-purple-600",
  },
  {
    id: "pnc",
    name: "PNC",
    logo: getBankLogoUrl("pnc.com"),
    color: "bg-orange-600",
  },
  {
    id: "td",
    name: "TD Bank",
    logo: getBankLogoUrl("td.com"),
    color: "bg-green-600",
  },
  {
    id: "schwab",
    name: "Charles Schwab",
    logo: getBankLogoUrl("schwab.com"),
    color: "bg-sky-600",
  },
  {
    id: "ally",
    name: "Ally Bank",
    logo: getBankLogoUrl("ally.com"),
    color: "bg-violet-600",
  },
  {
    id: "discover",
    name: "Discover",
    logo: getBankLogoUrl("discover.com"),
    color: "bg-orange-500",
  },
  {
    id: "goldman",
    name: "Goldman Sachs",
    logo: getBankLogoUrl("goldmansachs.com"),
    color: "bg-blue-900",
  },
  {
    id: "sofi",
    name: "SoFi",
    logo: getBankLogoUrl("sofi.com"),
    color: "bg-cyan-600",
  },
  { id: "other", name: "Other", logo: "", color: "bg-muted" },
];

export const getBankById = (id: string) => banks.find((b) => b.id === id);
export const getBankByName = (name: string) =>
  banks.find((b) => b.name.toLowerCase() === name.toLowerCase());
