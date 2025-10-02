import { fetchUsers } from "./api";

describe("API Functions", () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it("fetches users successfully", async () => {
    global.fetch = jest.fn(() =>
      Promise.resolve({
        ok: true,
        json: () =>
          Promise.resolve([
            { id: 1, name: "John Doe", email: "john@example.com" },
            { id: 2, name: "Jane Smith", email: "jane@example.com" },
          ]),
      }),
    ) as jest.Mock;

    const users = await fetchUsers();

    expect(users).toHaveLength(2);
    expect(users[0].name).toBe("John Doe");
    expect(users[1].name).toBe("Jane Smith");
  });

  it("handles API errors", async () => {
    global.fetch = jest.fn(() =>
      Promise.resolve({
        ok: false,
        status: 500,
      }),
    ) as jest.Mock;

    await expect(fetchUsers()).rejects.toThrow("Failed to fetch users");
  });
});
