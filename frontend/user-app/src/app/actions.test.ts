// jest.mock は import より前に配置する必要がある（Jestがhoistする）
jest.mock("next/cache", () => ({
  revalidatePath: jest.fn(),
}));

import { saveUser } from "./actions";
import { revalidatePath } from "next/cache";

describe("Server Actions", () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it("saves user and revalidates path", async () => {
    const userData = { name: "John Doe", email: "john@example.com" };

    const result = await saveUser(userData);

    expect(result.success).toBe(true);
    expect(revalidatePath).toHaveBeenCalledWith("/users");
  });

  it("handles validation errors", async () => {
    const invalidData = { name: "", email: "invalid-email" };

    const result = await saveUser(invalidData);

    expect(result.success).toBe(false);
    expect(result.error).toBeDefined();
    expect(revalidatePath).not.toHaveBeenCalled();
  });
});
