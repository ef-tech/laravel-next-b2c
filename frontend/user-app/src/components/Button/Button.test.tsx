import { render, screen, fireEvent } from "../../../../../test-utils/render";
import { Button } from "./Button";
import mockRouter from "next-router-mock";

describe("Button Component", () => {
  beforeEach(() => {
    mockRouter.setCurrentUrl("/");
  });

  it("renders with correct text", () => {
    render(<Button>Click me</Button>);
    expect(screen.getByText("Click me")).toBeInTheDocument();
  });

  it("handles click events", () => {
    const handleClick = jest.fn();
    render(<Button onClick={handleClick}>Click me</Button>);

    const button = screen.getByText("Click me");
    fireEvent.click(button);

    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it("navigates on button click when href is provided", () => {
    render(<Button href="/dashboard">Go to Dashboard</Button>);

    const link = screen.getByText("Go to Dashboard");
    expect(link).toHaveAttribute("href", "/dashboard");
  });

  it("renders with different variants", () => {
    const { rerender } = render(<Button variant="primary">Primary</Button>);
    const primaryButton = screen.getByText("Primary");
    expect(primaryButton).toHaveClass("bg-blue-600");

    rerender(<Button variant="secondary">Secondary</Button>);
    const secondaryButton = screen.getByText("Secondary");
    expect(secondaryButton).toHaveClass("bg-gray-600");
  });
});
