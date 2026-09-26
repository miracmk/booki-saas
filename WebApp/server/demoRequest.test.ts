import { beforeEach, describe, expect, it, vi } from "vitest";

const { sendDemoRequestEmail } = vi.hoisted(() => ({ sendDemoRequestEmail: vi.fn() }));
vi.mock("./demoEmail", () => ({ sendDemoRequestEmail }));

import { appRouter } from "./routers";
import type { TrpcContext } from "./_core/context";

function createContext(): TrpcContext {
  return {
    user: null,
    req: { protocol: "https", headers: {} } as TrpcContext["req"],
    res: {} as TrpcContext["res"],
  };
}

describe("demo.request", () => {
  beforeEach(() => sendDemoRequestEmail.mockReset());

  it("validates form fields and sends a normalized request", async () => {
    sendDemoRequestEmail.mockResolvedValue(undefined);
    const caller = appRouter.createCaller(createContext());

    await expect(caller.demo.request({
      name: "  Deniz Kaya  ",
      email: " deniz@example.com ",
      phone: "0532 123 45 67",
      preferredContactTime: "12.00–15.00",
      businessType: " Salon & güzellik ",
    })).resolves.toEqual({ success: true });

    expect(sendDemoRequestEmail).toHaveBeenCalledWith({
      name: "Deniz Kaya",
      email: "deniz@example.com",
      phone: "0532 123 45 67",
      preferredContactTime: "12.00–15.00",
      businessType: "Salon & güzellik",
    });
  });

  it("rejects malformed email addresses", async () => {
    const caller = appRouter.createCaller(createContext());

    await expect(caller.demo.request({
      name: "Deniz Kaya",
      email: "not-an-email",
      phone: "0532 123 45 67",
      preferredContactTime: "12.00–15.00",
      businessType: "Salon & güzellik",
    })).rejects.toMatchObject({ code: "BAD_REQUEST" });

    expect(sendDemoRequestEmail).not.toHaveBeenCalled();
  });
});
