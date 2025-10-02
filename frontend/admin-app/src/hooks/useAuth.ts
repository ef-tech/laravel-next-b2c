"use client";

import { useSearchParams } from "next/navigation";
import { useState, useEffect } from "react";

interface User {
  id: number;
  name: string;
  email: string;
}

export function useAuth() {
  const searchParams = useSearchParams();
  const token = searchParams.get("token");
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchUser() {
      if (!token) {
        setLoading(false);
        return;
      }

      try {
        const response = await fetch(`/api/user?token=${token}`);
        if (response.ok) {
          const userData = await response.json();
          setUser(userData);
        }
      } catch (error) {
        console.error("Failed to fetch user:", error);
      } finally {
        setLoading(false);
      }
    }

    fetchUser();
  }, [token]);

  return { token, user, loading };
}
