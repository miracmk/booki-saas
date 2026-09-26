import { beforeEach, describe, expect, it, vi } from "vitest";

const { sendDemoRequestEmail } = vi.hoisted(() => ({ sendDemoRequestEmail: vi.fn() }));
vi.mock("./demoEmail", () => ({ sendDemoRequestEmail }));

import { appRouter } from "./routers";
import type { TrpcContext } from "./_core/context";

function createContext(): TrpcContext {
  return { user: null, req: { protocol: "https", headers: {} } as TrpcContext["req"], res: {} as TrpcContext["res"] };
}

describe("trial.request", () => {
  beforeEach(() => sendDemoRequestEmail.mockReset());

  it("sends a normalized free-trial request with the selected plan", async () => {
    sendDemoRequestEmail.mockResolvedValue(undefined);
    const caller = appRouter.createCaller(createContext());
    await expect(caller.trial.request({
      name: "  Ayşe Demir  ",
      email: " ayse@example.com ",
      phone: "0532 987 65 43",
      businessType: " Restoran & kafe ",
      plan: "Professional",
    })).resolves.toEqual({ success: true });
    expect(sendDemoRequestEmail).toHaveBeenCalledWith({
      name: "Ayşe Demir",
      email: "ayse@example.com",
      phone: "0532 987 65 43",
      preferredContactTime: "14 günlük deneme kaydı",
      businessType: "Restoran & kafe",
      plan: "Professional",
    });
  });

  it("rejects an unsupported plan", async () => {
    const caller = appRouter.createCaller(createContext());
    await expect(caller.trial.request({
      name: "Ayşe Demir",
      email: "ayse@example.com",
      phone: "0532 987 65 43",
      businessType: "Restoran & kafe",
      plan: "Plus" as "Starter",
    })).rejects.toMatchObject({ code: "BAD_REQUEST" });
  });
});
