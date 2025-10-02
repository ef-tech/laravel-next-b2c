"use client";

import Link from "next/link";
import { ReactNode } from "react";

interface ButtonProps {
  children: ReactNode;
  onClick?: () => void;
  variant?: "primary" | "secondary";
  href?: string;
}

export function Button({ children, onClick, variant = "primary", href }: ButtonProps) {
  const baseClasses = "px-4 py-2 rounded font-medium";
  const variantClasses =
    variant === "primary" ? "bg-blue-600 text-white" : "bg-gray-600 text-white";
  const className = `${baseClasses} ${variantClasses}`;

  if (href) {
    return (
      <Link href={href} className={className}>
        {children}
      </Link>
    );
  }

  return (
    <button onClick={onClick} className={className}>
      {children}
    </button>
  );
}
