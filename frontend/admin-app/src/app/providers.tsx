"use client";

import { AdminAuthProvider } from "@/contexts/AdminAuthContext";

export function Providers({ children }: { children: React.ReactNode }) {
  return <AdminAuthProvider>{children}</AdminAuthProvider>;
}
