import { describe, expect, it } from "vitest";
import nodemailer from "nodemailer";

describe("demo email SMTP configuration", () => {
  it("authenticates against the configured SMTP server", async () => {
    const host = process.env.SMTP_HOST;
    const port = Number(process.env.SMTP_PORT || "465");
    const user = process.env.SMTP_USER;
    const password = process.env.SMTP_PASSWORD;

    expect(host).toBeTruthy();
    expect(user).toBeTruthy();
    expect(password).toBeTruthy();

    const transporter = nodemailer.createTransport({
      host,
      port,
      secure: port === 465,
      auth: { user, pass: password },
      connectionTimeout: 8_000,
      greetingTimeout: 8_000,
      socketTimeout: 8_000,
    });

    await expect(transporter.verify()).resolves.toBe(true);
    transporter.close();
  }, 15_000);
});
