import { useState } from 'react';
import { Building2 } from 'lucide-react';
import { cn } from '@/lib/utils';

interface BankLogoProps {
  bank: {
    name: string;
    logo: string;
  };
  className?: string;
}

export function BankLogo({ bank, className }: BankLogoProps) {
  const [error, setError] = useState(false);

  if (!bank.logo || error) {
    return (
      <div
        className={cn(
          'bg-muted rounded-md flex items-center justify-center',
          className
        )}
      >
        <Building2 className="w-6 h-6 text-gray-400" />
      </div>
    );
  }

  return (
    <img
      src={bank.logo}
      alt={bank.name}
      className={cn('rounded-md object-contain', className)}
      onError={() => setError(true)}
    />
  );
}
