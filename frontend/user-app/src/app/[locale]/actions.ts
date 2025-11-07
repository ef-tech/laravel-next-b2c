"use server";

import { revalidatePath } from "next/cache";

interface UserData {
  name: string;
  email: string;
}

interface SaveUserResult {
  success: boolean;
  error?: string;
}

export async function saveUser(data: UserData): Promise<SaveUserResult> {
  // Validation
  if (!data.name || !data.email || !data.email.includes("@")) {
    return {
      success: false,
      error: "Invalid user data",
    };
  }

  // Simulate saving user (in real app, this would call an API)
  // await fetch('/api/users', { method: 'POST', body: JSON.stringify(data) });

  // Revalidate the users page
  revalidatePath("/users");

  return {
    success: true,
  };
}
