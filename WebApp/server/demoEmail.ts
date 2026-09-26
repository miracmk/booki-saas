import nodemailer, { type Transporter } from "nodemailer";

export type DemoRequest = {
  name: string;
  email: string;
  phone: string;
  preferredContactTime: string;
  businessType: string;
  plan?: string;
};

let transporter: Transporter | null = null;

function getTransporter() {
  if (!process.env.SMTP_HOST || !process.env.SMTP_USER || !process.env.SMTP_PASSWORD) {
    throw new Error("SMTP configuration is missing");
  }

  if (!transporter) {
    const port = Number(process.env.SMTP_PORT || "465");
    transporter = nodemailer.createTransport({
      host: process.env.SMTP_HOST,
      port,
      secure: port === 465,
      auth: {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASSWORD,
      },
    });
  }

  return transporter;
}

export async function sendDemoRequestEmail(input: DemoRequest) {
  const recipient = process.env.DEMO_RECIPIENT || process.env.SMTP_USER;
  if (!recipient) throw new Error("Demo email recipient is missing");

  const submittedAt = new Intl.DateTimeFormat("tr-TR", {
    dateStyle: "long",
    timeStyle: "short",
    timeZone: "Europe/Istanbul",
  }).format(new Date());

  await getTransporter().sendMail({
    from: `BooKi Demo <${process.env.SMTP_USER}>`,
    to: recipient,
    replyTo: input.email,
    subject: `Yeni BooKi demo talebi — ${input.name}`,
    text: [
      "Yeni BooKi demo talebi",
      "",
      `Ad soyad: ${input.name}`,
      `E-posta: ${input.email}`,
      `Telefon: ${input.phone}`,
      `Tercih edilen iletişim zamanı: ${input.preferredContactTime}`,
      `İşletme türü: ${input.businessType}`,
      `Seçilen paket: ${input.plan || "Demo talebi"}`,
      `Gönderim zamanı: ${submittedAt}`,
      "",
      "Bu e-posta BooKi web sitesindeki demo talep formundan gönderildi.",
    ].join("\n"),
    html: `
      <div style="font-family:Arial,sans-serif;line-height:1.6;color:#10202d;max-width:620px">
        <div style="padding:24px;border-radius:16px;background:#0a1724;color:#efffff">
          <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#80d9d4">BooKi demo merkezi</div>
          <h1 style="margin:8px 0 0;font-size:24px">Yeni demo talebi</h1>
        </div>
        <div style="padding:24px;border:1px solid #dce7eb;border-top:0;border-radius:0 0 16px 16px">
          <p style="margin-top:0">Web sitesindeki demo formundan yeni bir talep geldi.</p>
          <table style="border-collapse:collapse;width:100%">
            <tr><td style="padding:10px 0;color:#718390;width:160px">Ad soyad</td><td style="padding:10px 0;font-weight:700">${escapeHtml(input.name)}</td></tr>
            <tr><td style="padding:10px 0;color:#718390">E-posta</td><td style="padding:10px 0;font-weight:700"><a href="mailto:${escapeHtml(input.email)}">${escapeHtml(input.email)}</a></td></tr>
            <tr><td style="padding:10px 0;color:#718390">Telefon</td><td style="padding:10px 0;font-weight:700"><a href="tel:${escapeHtml(input.phone)}">${escapeHtml(input.phone)}</a></td></tr>
            <tr><td style="padding:10px 0;color:#718390">İletişim zamanı</td><td style="padding:10px 0;font-weight:700">${escapeHtml(input.preferredContactTime)}</td></tr>
            <tr><td style="padding:10px 0;color:#718390">İşletme türü</td><td style="padding:10px 0;font-weight:700">${escapeHtml(input.businessType)}</td></tr>
            <tr><td style="padding:10px 0;color:#718390">Seçilen paket</td><td style="padding:10px 0;font-weight:700">${escapeHtml(input.plan || "Demo talebi")}</td></tr>
            <tr><td style="padding:10px 0;color:#718390">Gönderim zamanı</td><td style="padding:10px 0">${escapeHtml(submittedAt)}</td></tr>
          </table>
        </div>
      </div>
    `,
  });
}

function escapeHtml(value: string) {
  return value.replace(/[&<>'"]/g, character => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    "'": "&#39;",
    '"': "&quot;",
  })[character] || character);
}
