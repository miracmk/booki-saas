import { useState } from "react";
import { useLocation, Link } from "wouter";
import { SEOHead } from "@/components/SEOHead";
import { Navbar } from "@/components/Navbar";
import { Footer } from "@/components/Footer";
import { StickyContactBar } from "@/components/StickyContactBar";
import { ShieldCheck, Building2, FileText, Truck, Scale } from "lucide-react";

type Lang = "en" | "tr";
type DocType = "privacy" | "terms" | "about" | "mesafeli" | "teslimat";

function getDocType(path: string): DocType {
  if (path.includes("privacy") || path.includes("gizlilik")) return "privacy";
  if (path.includes("mesafeli")) return "mesafeli";
  if (path.includes("teslimat") || path.includes("iade")) return "teslimat";
  if (path.includes("hakkimizda") || path.includes("about")) return "about";
  return "terms";
}

const SEO_META: Record<DocType, { title: string; description: string; canonicalPath: string }> = {
  privacy: {
    title: "Privacy Policy / Gizlilik Politikası | BooKi",
    description:
      "BooKi Appointment & Booking Platform Privacy Policy. Comprehensive disclosures on Google Calendar data access, usage, protection, and Google API Services compliance.",
    canonicalPath: "/privacy",
  },
  terms: {
    title: "Terms of Service / Kullanım Şartları | BooKi",
    description: "BooKi online appointment scheduling and booking platform Terms of Service and acceptable use policies.",
    canonicalPath: "/terms",
  },
  about: {
    title: "About BooKi / Hakkımızda | BooKi",
    description:
      "About BooKi — the online appointment scheduling and business management platform developed by Ki Software (Ki Business Solutions) for service-oriented businesses.",
    canonicalPath: "/hakkimizda",
  },
  mesafeli: {
    title: "Mesafeli Satış Sözleşmesi / Distance Sales Contract | BooKi",
    description:
      "BooKi Mesafeli Satış Sözleşmesi (Distance Sales Contract). 6502 SKHK ve Mesafeli Sözleşmeler Yönetmeliği uyarınca iyzico sanal POS ile yapılan uzaktan satışlara ilişkin hükümler.",
    canonicalPath: "/mesafeli-satis",
  },
  teslimat: {
    title: "Teslimat ve İade Politikası / Delivery & Returns | BooKi",
    description:
      "BooKi dijital hizmetlerinin teslimatı (aktivasyonu) ve iyzico üzerinden iptal/iade süreçleri — refund and cancellation policy for BooKi digital services.",
    canonicalPath: "/teslimat-iade",
  },
};

const DOC_ICONS: Record<DocType, typeof ShieldCheck> = {
  privacy: ShieldCheck,
  terms: Scale,
  about: Building2,
  mesafeli: FileText,
  teslimat: Truck,
};

const highlightGreen = {
  background: "#f0fdf4",
  borderLeft: "5px solid #16a34a",
  padding: "1.25rem",
  borderRadius: "8px",
  margin: "1.5rem 0",
};

const highlightAmber = {
  background: "#fffbeb",
  borderLeft: "5px solid #d97706",
  padding: "1.25rem",
  borderRadius: "8px",
  margin: "1.5rem 0",
};

function updateLine(doc: DocType, lang: Lang): string {
  const dev = lang === "en" ? "Developer: Ki Software (Ki Business Solutions)" : "Geliştirici: Ki Software (Ki Business Solutions)";
  switch (doc) {
    case "about":
      return `BooKi Appointment & Booking Platform | ${dev}`;
    case "mesafeli":
      return lang === "en"
        ? `Version: 1.0 | Effective Date: 23.09.2026 | Seller: Ki Software / Ki Business Solutions`
        : `Versiyon: 1.0 | Yürürlük Tarihi: 23.09.2026 | Satıcı: Ki Software / Ki Business Solutions`;
    case "teslimat":
      return lang === "en"
        ? `Version: 1.0 | Effective Date: 23.09.2026 | Payment Provider: iyzico (Visa · Mastercard)`
        : `Versiyon: 1.0 | Yürürlük Tarihi: 23.09.2026 | Ödeme Kuruluşu: iyzico (Visa · Mastercard)`;
    default:
      return lang === "en"
        ? "Effective Date: September 19, 2026 | Developer: Ki Software (Ki Business Solutions)"
        : "Yürürlük Tarihi: 19 Eylül 2026 | Geliştirici: Ki Software (Ki Business Solutions)";
  }
}

function heading(doc: DocType, lang: Lang): string {
  switch (doc) {
    case "privacy":
      return lang === "en" ? "BooKi Privacy Policy" : "BooKi Gizlilik Politikası";
    case "about":
      return lang === "en" ? "About BooKi" : "Hakkımızda";
    case "mesafeli":
      return lang === "en" ? "Distance Sales Contract" : "Mesafeli Satış Sözleşmesi";
    case "teslimat":
      return lang === "en" ? "Delivery and Returns Policy" : "Teslimat ve İade Politikası";
    default:
      return lang === "en" ? "BooKi Terms of Service" : "BooKi Kullanım Şartları";
  }
}

function PrivacyContent({ lang }: { lang: Lang }) {
  if (lang === "en") {
    return (
      <>
        <h2>1. Introduction &amp; Application Identity</h2>
        <p>
          This Privacy Policy describes how <strong>BooKi</strong> ("BooKi", "Application", "we", "us", or "our"), an online appointment scheduling and business management platform developed and operated by <strong>Ki Software</strong> (a division of <strong>Ki Business Solutions</strong>, website: <a href="https://kibusiness.co" target="_blank" rel="noopener noreferrer">kibusiness.co</a>), collects, accesses, uses, stores, protects, and discloses information when you access or use our services via our website (<strong>https://booki.kibusiness.co</strong>) and associated software endpoints (<strong>https://bookiapp.kibusiness.co</strong>).
        </p>
        <p>
          We are dedicated to safeguarding your privacy and ensuring full compliance with the European Union General Data Protection Regulation (GDPR), the Turkish Law on the Protection of Personal Data (KVKK No. 6698), and Google's API Services User Data Policy.
        </p>

        <h2>2. Personal Data We Collect</h2>
        <p>
          To deliver seamless appointment bookings, customer communication, and calendar management, BooKi collects the following categories of information:
        </p>
        <ul>
          <li><strong>Account Details:</strong> Full name, business trade name, phone number, email address, physical salon address, and authentication credentials.</li>
          <li><strong>Customer &amp; Appointment Records:</strong> Client names, phone numbers, email addresses, selected services, assigned staff members, appointment dates and times, duration, session notes, and transaction values.</li>
          <li><strong>Technical &amp; Log Data:</strong> IP addresses, browser types, device information, and security session cookies.</li>
        </ul>

        <h2>3. Google User Data Accessed, Used &amp; Disclosed</h2>
        <p>
          BooKi provides integration with Google APIs (primarily <strong>Google Calendar</strong>) allowing businesses and service providers to synchronize their appointments with their Google Calendar. When you choose to link your Google Account with BooKi, our application requests access to specific scopes only upon your explicit consent.
        </p>

        <div style={highlightGreen}>
          <h4 style={{ color: "#15803d", margin: "0 0 0.5rem 0", display: "flex", alignItems: "center", gap: "0.5rem" }}>
            <ShieldCheck size={20} /> Google API Services User Data Policy Compliance
          </h4>
          <p style={{ color: "#166534", margin: 0 }}>
            BooKi's use and transfer to any other app of information received from Google APIs adheres to the
            <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener noreferrer" style={{ color: "#15803d", fontWeight: "bold" }}> Google API Services User Data Policy</a>, including the Limited Use requirements.
          </p>
        </div>

        <div style={{ overflowX: "auto", margin: "1.5rem 0" }}>
          <table style={{ width: "100%", borderCollapse: "collapse", border: "1px solid #e2e8f0" }}>
            <thead>
              <tr style={{ background: "#f8fafc" }}>
                <th style={{ padding: "0.85rem", border: "1px solid #e2e8f0", textAlign: "left" }}>Google OAuth Scope</th>
                <th style={{ padding: "0.85rem", border: "1px solid #e2e8f0", textAlign: "left" }}>Google User Data Accessed</th>
                <th style={{ padding: "0.85rem", border: "1px solid #e2e8f0", textAlign: "left" }}>Purpose &amp; Specific Use</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td style={{ padding: "0.85rem", border: "1px solid #e2e8f0" }}><code>openid</code>, <code>.../userinfo.email</code></td>
                <td style={{ padding: "0.85rem", border: "1px solid #e2e8f0" }}>User ID, email address, profile name</td>
                <td style={{ padding: "0.85rem", border: "1px solid #e2e8f0" }}>User authentication, identity verification, account registration.</td>
              </tr>
              <tr>
                <td style={{ padding: "0.85rem", border: "1px solid #e2e8f0" }}><code>.../auth/calendar</code></td>
                <td style={{ padding: "0.85rem", border: "1px solid #e2e8f0" }}>Calendar titles, event summaries, start/end dates and times, attendees, calendar IDs</td>
                <td style={{ padding: "0.85rem", border: "1px solid #e2e8f0" }}>
                  <strong>Two-way calendar sync:</strong> 1. Inserting BooKi appointments to Google Calendar; 2. Reading busy intervals to block unavailable hours and avoid double-bookings.
                </td>
              </tr>
              <tr>
                <td style={{ padding: "0.85rem", border: "1px solid #e2e8f0" }}><code>.../auth/spreadsheets</code>, <code>.../auth/drive.file</code></td>
                <td style={{ padding: "0.85rem", border: "1px solid #e2e8f0" }}>Spreadsheet content in user-selected files</td>
                <td style={{ padding: "0.85rem", border: "1px solid #e2e8f0" }}>Exporting appointment logs and revenue reports to a Google Sheet chosen by the user.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <h2>4. Prohibited Uses of Google User Data</h2>
        <ul>
          <li><strong>No Sale of Data:</strong> We will <strong>NEVER sell, rent, lease, or trade</strong> Google user data to third parties, data brokers, or advertising entities.</li>
          <li><strong>No Targeted Advertising:</strong> Google user data is <strong>NEVER used or disclosed</strong> for serving advertisements, personalized ads, retargeted ads, or interest-based ads.</li>
          <li><strong>No Creditworthiness:</strong> Google user data is never used for creditworthiness evaluation or lending purposes.</li>
        </ul>

        <div style={highlightAmber}>
          <h4 style={{ color: "#b45309", margin: "0 0 0.5rem 0" }}>
            🤖 AI / Machine Learning (ML) Affirmation
          </h4>
          <p style={{ color: "#92400e", margin: 0 }}>
            <strong>BooKi explicitly affirms that Google Workspace APIs and data obtained from Google APIs are NOT used to develop, build, improve, or train generalized, foundational, or non-personalized artificial intelligence (AI) and/or machine learning (ML) models.</strong>
          </p>
        </div>

        <h2>5. Data Security &amp; Protection Mechanisms</h2>
        <ul>
          <li><strong>Encryption in Transit:</strong> Transport Layer Security (TLS 1.3 / HTTPS) with modern cipher suites.</li>
          <li><strong>Encryption at Rest:</strong> AES-256-GCM authenticated encryption for OAuth access tokens, refresh tokens, and PII.</li>
          <li><strong>Blind Indexing:</strong> HMAC-SHA256 for secure search queries.</li>
        </ul>

        <h2>6. Data Retention &amp; Right to Deletion (User Control)</h2>
        <ul>
          <li><strong>Disconnecting Google Calendar:</strong> You can disconnect at any time in BooKi via <strong>Settings &gt; Integrations &gt; Google Calendar &gt; Disconnect</strong>, immediately purging stored OAuth tokens.</li>
          <li><strong>Google Permissions Portal:</strong> Access can be revoked anytime at <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener noreferrer">myaccount.google.com/permissions</a>.</li>
          <li><strong>Account Erasure:</strong> To delete your account and all associated data, email <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a>. Requests are completed within 30 days.</li>
        </ul>

        <h2>7. Contact Information</h2>
        <p>
          <strong>Ki Software / Ki Business Solutions</strong><br />
          Address: Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa, Türkiye<br />
          Official Website: <a href="https://kibusiness.co" target="_blank" rel="noopener noreferrer">kibusiness.co</a><br />
          Privacy &amp; Data Erasure: <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a><br />
          Support: <a href="mailto:hello@kibusiness.co">hello@kibusiness.co</a>
        </p>
      </>
    );
  }
  return (
    <>
      <h2>1. Veri Sorumlusu ve Uygulama Bilgileri</h2>
      <p>
        Bu Gizlilik Politikası, <strong>Ki Business Solutions</strong> bünyesindeki <strong>Ki Software</strong> tarafından geliştirilen ve işletilen <strong>BooKi</strong> online randevu ve masa rezervasyon platformunun kişisel veri işleme ilkelerini açıklar.
      </p>

      <h2>2. Google Kullanıcı Verileri ve Kullanım Şekli</h2>
      <p>
        BooKi, personelin ve işletmelerin randevularını kendi Google Takvimleri ile çakışmasız senkronize edebilmesi amacıyla Google Calendar API ile entegre çalışır.
      </p>
      <ul>
        <li><strong>Çift yönlü takvim eşitlemesi:</strong> BooKi üzerinden alınan randevuların seçtiğiniz Google Takvimine işlenmesi ve mevcut meşguliyetlerinizin okunarak çifte randevunun engellenmesi.</li>
        <li><strong>Veri Satışı Yasağı:</strong> Google kullanıcı verileri hiçbir şart altında üçüncü taraflara, veri simsarlarına veya reklam şirketlerine satılmaz veya devredilmez.</li>
        <li><strong>Hedefli Reklam Yasağı:</strong> Veriler reklam, pazarlama veya kredi değerlendirmesi amacıyla kullanılamaz.</li>
        <li><strong>Yapay Zeka (AI/ML) Taahhüdü:</strong> Google API'leri ve kullanıcı verileri genel veya kişiselleştirilmemiş yapay zeka (AI) ve makine öğrenimi (ML) modellerini geliştirmek veya eğitmek amacıyla KESİNLİKLE KULLANILMAZ.</li>
      </ul>

      <h2>3. Veri Güvenliği ve Silme Hakkı</h2>
      <ul>
        <li>Aktarımda TLS 1.3 / HTTPS, veritabanında OAuth belirteçleri için AES-256-GCM şifreleme kullanılmaktadır.</li>
        <li>Google entegrasyonu dilediğiniz an <strong>Ayarlar &gt; Entegrasyonlar &gt; Google Takvim &gt; Bağlantıyı Kes</strong> seçeneği ile sonlandırılabilir ve veriler derhal silinir.</li>
        <li>Tüm kişisel verilerinizin imhası için <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a> adresine başvurabilirsiniz.</li>
      </ul>

      <h2>4. İletişim</h2>
      <p>
        <strong>Ki Software / Ki Business Solutions</strong><br />
        Adres: Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa<br />
        Web: <a href="https://kibusiness.co" target="_blank" rel="noopener noreferrer">kibusiness.co</a><br />
        E-posta: <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a>
      </p>
    </>
  );
}

function TermsContent({ lang }: { lang: Lang }) {
  if (lang === "en") {
    return (
      <>
        <h2>1. Acceptance of Terms</h2>
        <p>
          These Terms of Service govern your use of the BooKi appointment scheduling platform operated by <strong>Ki Software (Ki Business Solutions)</strong>. By using the service, you agree to these Terms and our Privacy Policy.
        </p>
        <h2>2. Google API Services Compliance</h2>
        <p>
          When you link your Google Account with BooKi, you authorize BooKi to synchronize calendar events. BooKi adheres to the <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener noreferrer">Google API Services User Data Policy</a>, including the Limited Use requirements.
        </p>
        <h2>3. Acceptable Use &amp; Liability</h2>
        <p>
          You agree not to misuse the platform, violate applicable data protection laws, or attempt unauthorized access. Services are provided "as-is" under applicable laws of Turkey.
        </p>
        <h2>4. Contact</h2>
        <p>
          <strong>Ki Software / Ki Business Solutions</strong><br />
          Address: Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa, Türkiye<br />
          For inquiries: <a href="mailto:hello@kibusiness.co">hello@kibusiness.co</a>
        </p>
      </>
    );
  }
  return (
    <>
      <h2>1. Şartların Kabulü</h2>
      <p>
        Bu Kullanım Şartları, <strong>Ki Software / Ki Business Solutions</strong> tarafından sunulan BooKi platformuna erişiminizi düzenler.
      </p>
      <h2>2. Google API Hizmetleri Uyumu</h2>
      <p>
        Google Takvim entegrasyonu, Google API Hizmetleri Kullanıcı Verisi Politikası'nın Sınırlı Kullanım gereksinimlerine tabidir.
      </p>
      <h2>3. İletişim</h2>
      <p>
        <strong>Ki Software / Ki Business Solutions</strong><br />
        Adres: Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa<br />
        Sorularınız için: <a href="mailto:hello@kibusiness.co">hello@kibusiness.co</a>
      </p>
    </>
  );
}

function AboutContent({ lang }: { lang: Lang }) {
  if (lang === "en") {
    return (
      <>
        <h2>Who We Are</h2>
        <p>
          <strong>BooKi</strong> is an online appointment scheduling and business management platform developed by <strong>Ki Software</strong> (a division of <strong>Ki Business Solutions</strong>). We build practical, cloud-based tools for service-oriented businesses — including beauty salons, barbershops, spas, clinics and studios — so they can manage appointments, staff, customers and payments from a single dashboard.
        </p>

        <h2>What BooKi Offers</h2>
        <ul>
          <li>Online appointment booking and calendar management with double-booking prevention.</li>
          <li>Staff and commission management with session check-in / check-out tracking.</li>
          <li>Customer relationship management and reservation history.</li>
          <li>Secure online payments processed through iyzico (Visa and Mastercard supported).</li>
          <li>Third-party calendar synchronization, including Google Calendar.</li>
          <li>A public marketplace ("RandevuBurada") where businesses can be discovered by new customers.</li>
        </ul>

        <h2>Our Commitment</h2>
        <p>
          We are committed to reliability, security and privacy. BooKi handles personal data in accordance with the Turkish Personal Data Protection Law (KVKK) and the EU General Data Protection Regulation (GDPR). Payment transactions are processed exclusively through PCI-DSS compliant payment provider iyzico; card details never touch our servers.
        </p>

        <h2>Contact</h2>
        <p>
          <strong>Ki Software / Ki Business Solutions</strong><br />
          Address: Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa, Türkiye<br />
          Website: <a href="https://kibusiness.co" target="_blank" rel="noopener noreferrer">https://kibusiness.co</a><br />
          Email: <a href="mailto:support@kibusiness.co">support@kibusiness.co</a><br />
          Legal Affairs: <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a>
        </p>
      </>
    );
  }
  return (
    <>
      <h2>Biz Kimiz</h2>
      <p>
        <strong>BooKi</strong>, <strong>Ki Software</strong> (<strong>Ki Business Solutions</strong> bünyesi) tarafından geliştirilen online randevu ve işletme yönetim platformudur. Kuaför, güzellik salonu, berber, spa, klinik ve stüdyo gibi hizmet işletmeleri için randevu, personel, müşteri ve ödemeleri tek panelde yönetmeye yarayan pratik, bulut tabanlı araçlar geliştiriyoruz.
      </p>

      <h2>BooKi'nin Sundukları</h2>
      <ul>
        <li>Çift rezervasyonu önleyen online randevu ve takvim yönetimi.</li>
        <li>Giriş/çıkış takibi ile personel ve komisyon yönetimi.</li>
        <li>Müşteri ilişkileri yönetimi ve rezervasyon geçmişi.</li>
        <li>iyzico üzerinden güvenli online ödeme desteği (Visa ve Mastercard).</li>
        <li>Google Takvim dahil üçüncü taraf takvim senkronizasyonu.</li>
        <li>İşletmelerin yeni müşterilere ulaşabildiği halka açık pazaryeri ("RandevuBurada").</li>
      </ul>

      <h2>Taahhüdümüz</h2>
      <p>
        Güvenilirlik, güvenlik ve gizlilik konularında kararlıyız. BooKi, kişisel verileri KVKK (Türkiye) ve GDPR (AB) mevzuatına uygun şekilde işler. Ödeme işlemleri yalnızca PCI-DSS uyumlu ödeme kuruluşu iyzico üzerinden gerçekleştirilir; kart bilgileri sunucularımıza asla ulaşmaz.
      </p>

      <h2>İletişim</h2>
      <p>
        <strong>Ki Software / Ki Business Solutions</strong><br />
        Adres: Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa<br />
        Web Sitesi: <a href="https://kibusiness.co" target="_blank" rel="noopener noreferrer">https://kibusiness.co</a><br />
        E-posta: <a href="mailto:support@kibusiness.co">support@kibusiness.co</a><br />
        Hukuki İşler: <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a>
      </p>
    </>
  );
}

function MesafeliContent({ lang }: { lang: Lang }) {
  if (lang === "en") {
    return (
      <>
        <h2>1. Parties</h2>
        <p>
          This Distance Sales Contract is concluded in accordance with Turkish Law No. 6502 on the Protection of Consumers and the Regulation on Distance Contracts, upon acceptance of this contract text on the BooKi platform (booki.kibusiness.co and its subdomains).
        </p>
        <ul>
          <li><strong>SELLER:</strong> Ki Software / Ki Business Solutions — Address: Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa, Türkiye — web: <a href="https://software.kibusiness.co" target="_blank" rel="noopener noreferrer">software.kibusiness.co</a>, email: <a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a></li>
          <li><strong>BUYER / CONSUMER:</strong> The natural or legal person who registers on the BooKi platform and accepts this contract to become a party to the distance sale transaction.</li>
        </ul>

        <h2>2. Subject of the Contract and Scope</h2>
        <p>
          This contract covers the purchase, payment, usage terms of the BooKi subscription and related services ("SERVICE") supplied by the SELLER to the BUYER, and the payment of the price collected through the iyzico virtual POS for the SERVICE.
        </p>
        <div style={highlightAmber}>
          <h4 style={{ color: "#b45309", margin: "0 0 0.5rem 0" }}>Scope of the SERVICE</h4>
          <p style={{ color: "#92400e", margin: 0 }}>
            Online appointment scheduling and calendar management, staff and commission management, customer management, Google Calendar synchronization, marketplace listing and online payment collection via iyzico.
          </p>
        </div>

        <h2>3. Contract Date, Payment and Delivery</h2>
        <ul>
          <li><strong>Contract Date:</strong> The date the BUYER approves the contract at the payment step.</li>
          <li><strong>Payment:</strong> Payment is made through the iyzico virtual POS infrastructure via credit/debit card (Visa, Mastercard) or bank transfer. Card details are processed securely by iyzico and are never stored by the SELLER.</li>
          <li><strong>Delivery / Activation:</strong> The SERVICE is a digital service; no physical delivery takes place. Following payment approval, the subscription is assigned to the account immediately and activation details are sent by email.</li>
        </ul>

        <h2>4. Right of Withdrawal</h2>
        <p>
          Under Law No. 6502 and the Regulation on Distance Contracts, the BUYER has the right to withdraw from the contract <strong>within fourteen (14) days</strong> from the date the contract is concluded, without giving any reason and without penalty.
        </p>
        <div style={highlightAmber}>
          <h4 style={{ color: "#b45309", margin: "0 0 0.5rem 0" }}>Digital Content Exemption</h4>
          <p style={{ color: "#92400e", margin: 0 }}>
            Under Art. 15(1)(ğ) of the Regulation on Distance Contracts, subscription/membership contracts whose performance has started (and been completed) with the BUYER's express consent — i.e. services performed immediately in an electronic environment — are exempt from the right of withdrawal. Because the BooKi SERVICE is performed immediately upon activation, no right of withdrawal exists after the subscription is activated, and the collected price is not refunded.
          </p>
        </div>

        <h2>5. Withdrawal Notice and Refund (Where Withdrawal Applies)</h2>
        <p>
          In cases where the right of withdrawal still applies, the BUYER must send the withdrawal notice in writing to the SELLER's email address (<a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a>) or via the relevant section of the BooKi platform.
        </p>
        <ol>
          <li>The SELLER shall refund the collected price, pro-rated to the SERVICE used, no later than <strong>14 days</strong> from the date the withdrawal notice is received.</li>
          <li>Refunds are made via iyzico to the original card/account used for payment; see the <Link href="/teslimat-iade">Delivery &amp; Returns Policy</Link> page for details.</li>
        </ol>

        <h2>6. Service Operation and Consumer Requests</h2>
        <ul>
          <li>Maintenance and security measures required by law are applied to maintain continuity of the SERVICE.</li>
          <li>Short scheduled or emergency maintenance interruptions may occur and will be announced in advance.</li>
          <li>Consumer requests are handled via <a href="mailto:support@kibusiness.co">support@kibusiness.co</a>.</li>
        </ul>

        <h2>7. Applicable Law and Dispute Resolution</h2>
        <p>
          In disputes arising from this contract, Consumer Arbitration Committees and consumer courts are competent. Within the monetary limits set by Art. 68 of Law No. 6502, the relevant Consumer Arbitration Committee is the mandatory first instance. Turkish law applies to the formation and performance of this contract.
        </p>
      </>
    );
  }
  return (
    <>
      <h2>1. Taraflar</h2>
      <p>
        Bu Mesafeli Satış Sözleşmesi, 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği uyarınca, işbu sözleşme metninin BooKi platformu (booki.kibusiness.co ve alt alan adları) üzerinden onaylanması ile kurulur.
      </p>
      <ul>
        <li><strong>SATICI:</strong> Ki Software / Ki Business Solutions — Adres: Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa — web: <a href="https://software.kibusiness.co" target="_blank" rel="noopener noreferrer">software.kibusiness.co</a>, e-posta: <a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a></li>
        <li><strong>ALICI / TÜKETİCİ:</strong> BooKi platformuna kayıt olan ve işbu sözleşmeyi onaylayarak mesafeli satış işlemine taraf olan gerçek veya tüzel kişi.</li>
      </ul>

      <h2>2. Sözleşmenin Konusu ve Kapsamı</h2>
      <p>
        Bu sözleşme, ALICI tarafından SATICI'nın sağladığı ve aşağıda nitelik, miktar, süre ve kapsamı belirtilen BooKi abonelik ve hizmetlerinin ("HİZMET") satın alınması, ödenmesi, kullanım şartları ve bu HİZMET karşılığı iyzico sanal POS üzerinden alınacak bedelin ödenmesi ile ilgili hükümleri kapsar.
      </p>
      <div style={highlightAmber}>
        <h4 style={{ color: "#b45309", margin: "0 0 0.5rem 0" }}>HİZMET Kapsamı</h4>
        <p style={{ color: "#92400e", margin: 0 }}>
          Online randevu planlama ve takvim yönetimi, personel ve komisyon yönetimi, müşteri yönetimi, Google Takvim senkronizasyonu, marketplace listeleme ve iyzico üzerinden online ödeme alma özelliklerini içerir.
        </p>
      </div>

      <h2>3. Sözleşme Tarihi, Ödeme ve Teslim</h2>
      <ul>
        <li><strong>Sözleşme Tarihi:</strong> ALICI'nın ödeme adımında sözleşmeyi onayladığı tarihtir.</li>
        <li><strong>Ödeme:</strong> Ödeme, iyzico sanal POS altyapısı üzerinden kredi/banka kartı (Visa, Mastercard) veya banka havalesi ile yapılır. Kart bilgileri iyzico tarafından güvenli ortamda işlenir; SATICI tarafından saklanmaz.</li>
        <li><strong>Teslim / Aktivasyon:</strong> HİZMET dijital bir hizmettir; fiziki teslimat yapılmaz. Ödeme onayını takiben abonelik hesaba anında tanımlanır ve aktivasyon bilgileri e-posta ile iletilir.</li>
      </ul>

      <h2>4. Cayma Hakkı</h2>
      <p>
        ALICI, 6502 sayılı Kanun ve Mesafeli Sözleşmeler Yönetmeliği uyarınca, sözleşmenin kurulduğu tarihten itibaren <strong>on dört (14) gün</strong> içinde hiçbir gerekçe göstermeksizin ve cezai şart ödemeksizin sözleşmeden cayma hakkına sahiptir.
      </p>
      <div style={highlightAmber}>
        <h4 style={{ color: "#b45309", margin: "0 0 0.5rem 0" }}>Teslim Edilmeye Hazır Dijital İçerik İstisnası</h4>
        <p style={{ color: "#92400e", margin: 0 }}>
          Mesafeli Sözleşmeler Yönetmeliği md. 15(1)(ğ) uyarınca, ALICI'nın açık onayı ile ifasına başlanan ve ifası tamamlanan abonelik/üyelik sözleşmeleri ("elektronik ortamda anında ifa edilen hizmetler") cayma hakkının istisnasıdır. BooKi HİZMETİ, aktivasyon anında anında ifa edildiğinden, aboneliğin aktifleştirilmesi sonrası cayma hakkı bulunmamaktadır. Aktivasyonla birlikte tahsil edilen bedel iade edilmez.
        </p>
      </div>

      <h2>5. Cayma Bildirimi ve İade (Cayma Hakkı Olan Durumlarda)</h2>
      <p>
        Süresi içinde cayma hakkı bulunan hallerde ALICI, cayma bildirimini SATICI e-posta adresine (<a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a>) yazılı olarak ya da BooKi platformundaki ilgili sekme üzerinden iletmelidir.
      </p>
      <ol>
        <li>SATICI, cayma bildiriminin kendisine ulaştığı tarihten itibaren en geç <strong>14 gün</strong> içinde alınan bedeli, kullanılan HİZMET oranında iade eder.</li>
        <li>İade, ödemenin yapıldığı iyzico üzerinden orijinal kart/hesaba yapılır; buna ilişkin ek bilgi için <Link href="/teslimat-iade">Teslimat &amp; İade Politika</Link> sayfası incelenmelidir.</li>
      </ol>

      <h2>6. Hizmetin Yürütülmesi ve Tüketici Talepleri</h2>
      <ul>
        <li>HİZMET'in sürekliliği için yasal gerekliliklere uygun bakım ve güvenlik tedbirleri uygulanır.</li>
        <li>Platformda planlı veya zorunlu bakım nedeniyle kısa süreli kesinti olabilir; bu durumlar önceden duyurulur.</li>
        <li>Tüketici talepleri <a href="mailto:support@kibusiness.co">support@kibusiness.co</a> üzerinden değerlendirilir.</li>
      </ul>

      <h2>7. Uygulanacak Hukuk ve Uyuşmazlık Çözümü</h2>
      <p>
        Bu sözleşmeden doğan uyuşmazlıklarda Müşteri Hakem Heyetleri ve tüketici mahkemeleri yetkilidir. SATICI'nın yerleşim yeri dikkate alınarak Tüketicinin Korunması Hakkında Kanun md. 68 uyarınca belirlenen tüketici hakem heyetleri, parasal sınırlar dahilinde zorunlu başvuru merciidir. Sözleşmenin kurulması ve ifasında Türkiye Cumhuriyeti kanunları uygulanır.
      </p>
    </>
  );
}

function TeslimatContent({ lang }: { lang: Lang }) {
  if (lang === "en") {
    return (
      <>
        <h2>1. Delivery (Service Activation)</h2>
        <p>
          All services purchased on the BooKi platform are <strong>digital services</strong>; no physical product delivery takes place.
        </p>
        <ul>
          <li>As soon as the online payment amount is approved by the iyzico virtual POS, the payment is confirmed.</li>
          <li>Following payment approval, the SERVICE is <strong>immediately assigned and activated</strong> on the buyer's account.</li>
          <li>A confirmation email containing activation and login details is sent to the buyer's registered email address after payment.</li>
          <li>User guides and support are provided via <a href="mailto:support@kibusiness.co">support@kibusiness.co</a>.</li>
        </ul>

        <h2>2. Withdrawal and Cancellation</h2>
        <p>
          Digital subscription services are exempt from the right of withdrawal as services performed immediately upon activation (see <Link href="/mesafeli-satis">Distance Sales Contract</Link>, Art. 4). The refund process applies in the following cases:
        </p>
        <ul>
          <li><strong>Mistaken/Fraudulent payment:</strong> Refunded after verification of the charge.</li>
          <li><strong>Service never activated:</strong> If it is confirmed that account activation did not occur despite payment approval, the amount is refunded.</li>
          <li><strong>Exceptional cases where withdrawal legally applies:</strong> Within the 14-day withdrawal period, provided performance of the service has not started.</li>
        </ul>

        <h2>3. Refund Process (via iyzico)</h2>
        <ol>
          <li>A refund request is sent in writing to <a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a> or via the relevant section of the platform.</li>
          <li>After refund approval, the amount is reversed to the original iyzico transaction.</li>
          <li>Depending on the issuing bank/card network, the refund is usually reflected <strong>within 5-10 business days</strong>.</li>
          <li>Upon refund, the related subscription/service is terminated/cancelled at the same time.</li>
        </ol>

        <div style={highlightGreen}>
          <h4 style={{ color: "#15803d", margin: "0 0 0.5rem 0" }}>Important Note</h4>
          <p style={{ color: "#166534", margin: 0 }}>
            Due to banking regulations, iyzico can only make card refunds to the card/account used for the original payment. Transfer requests to a different account cannot be accepted.
          </p>
        </div>

        <h2>4. Refunds for Booking-Based Reservations (Marketplace)</h2>
        <p>
          For appointment payments made through the RandevuBurada marketplace, refunds are applied according to the business (seller)'s own cancellation/terms policy and the appointment status. Refund conditions are shown on the business detail page before payment; for issues related to a reservation, the business is the first point of contact.
        </p>

        <h2>5. Support and Disputes</h2>
        <p>
          For any questions about the refund process, contact <a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a> or write to our office at Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa. In case of a dispute, the arbitration committee / consumer court provisions in <Link href="/mesafeli-satis">Distance Sales Contract</Link> Art. 7 apply.
        </p>
      </>
    );
  }
  return (
    <>
      <h2>1. Teslimat (Hizmet Aktivasyonu)</h2>
      <p>
        BooKi platformu üzerinden satın alınan tüm hizmetler <strong>dijital hizmet</strong> niteliğindedir; bu nedenle fiziki ürün teslimatı yapılmaz.
      </p>
      <ul>
        <li>Online ödeme tutarı, iyzico sanal POS tarafından onaylandığı anda ödeme kesinleşir.</li>
        <li>Ödeme onayını takiben HİZMET, alıcının hesabına <strong>anında tanımlanır ve aktive edilir</strong>.</li>
        <li>Aktivasyon ve giriş bilgilerini içeren onay e-postası, ödeme sonrasında alıcının kayıtlı e-posta adresine iletilir.</li>
        <li>HİZMET'e ilişkin kullanım kılavuzu ve destek, <a href="mailto:support@kibusiness.co">support@kibusiness.co</a> üzerinden sağlanır.</li>
      </ul>

      <h2>2. Cayma ve İptal</h2>
      <p>
        Dijital abonelik hizmetleri, aktivasyon anında anında ifa edilen hizmetler kapsamında olduğundan <strong>cayma hakkının istisnası</strong>dır (bkz. <Link href="/mesafeli-satis">Mesafeli Satış Sözleşmesi</Link> md. 4). Aşağıdaki durumlarda iade süreci uygulanır:
      </p>
      <ul>
        <li><strong>Yanlışlıkla/Sahtekarlıkla yapılan ödeme:</strong> Nakit yaklaşımıyla, ödemenin doğrulanması sonrasında iade edilir.</li>
        <li><strong>Hizmetin hiç aktive edilmemesi:</strong> Ödeme onayına rağmen hesap aktivasyonunun gerçekleştirilmediği teyit edilirse tutar iade edilir.</li>
        <li><strong>Yasal olarak cayma hakkının doğduğu istisnai durumlar:</strong> 14 günlük cayma süresi içinde, hizmetin ifasına başlanmamış olması şartıyla.</li>
      </ul>

      <h2>3. İade Süreci (iyzico Üzerinden)</h2>
      <ol>
        <li>İade talebi, <a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a> adresine yazılı olarak veya platformdaki ilgili bölümden iletilir.</li>
        <li>İade onayı sonrasında tutar, ödemenin yapıldığı iyzico işlemine iade edilir.</li>
        <li>İadenin banka/cari hesaba yansıması, ödemenin yapıldığı kartın/banka kuruluşunun sürelerine bağlı olarak <strong>genellikle 5-10 iş günü</strong> içinde gerçekleşir.</li>
        <li>İade durumunda, ilgili abonelik/hizmet kullanımı aynı anda sonlandırılır/iptal edilir.</li>
      </ol>

      <div style={highlightAmber}>
        <h4 style={{ color: "#b45309", margin: "0 0 0.5rem 0" }}>Önemli Not</h4>
        <p style={{ color: "#92400e", margin: 0 }}>
          iyzico, bankacılık mevzuatı gereği kart iadelerini yalnızca ödemenin yapıldığı kart/hesaba yapabilir. Farklı bir hesaba transfer talepleri kabul edilemez.
        </p>
      </div>

      <h2>4. Randevu Bazlı Rezervasyonlarda İade (Pazaryeri)</h2>
      <p>
        RandevuBurada pazaryeri üzerinden yapılan randevu ödemelerinde iade; işletmenin (satıcının) kendi iptal/koşul politikasına ve randevu durumuna göre uygulanır. İade koşulları, ödeme öncesinde işletme detay sayfasında gösterilir; rezervasyona ilişkin sorunlarda ilk muhatap işletmedir.
      </p>

      <h2>5. Destek ve Uyuşmazlık</h2>
      <p>
        İade süreciyle ilgili her türlü soru için <a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a> veya Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa adresimiz üzerinden iletişime geçilebilir. Uyuşmazlık halinde <Link href="/mesafeli-satis">Mesafeli Satış Sözleşmesi</Link> md. 7'deki hakem heyeti/tüketici mahkemesi hükümleri geçerlidir.
      </p>
    </>
  );
}

export default function LegalPage() {
  const [location] = useLocation();
  const [lang, setLang] = useState<Lang>("en");
  const doc = getDocType(location);
  const seo = SEO_META[doc];
  const DocIcon = DOC_ICONS[doc];

  return (
    <div className="site-shell legal-page">
      <SEOHead title={seo.title} description={seo.description} canonicalPath={seo.canonicalPath} />

      <Navbar />

      <main id="main-content" className="legal-content-main">
        <section className="legal-container">
          <div className="legal-header" style={{ display: "flex", flexDirection: "column", gap: "1rem" }}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", flexWrap: "wrap", gap: "1rem" }}>
              <div style={{ display: "flex", alignItems: "center", gap: "0.75rem" }}>
                <DocIcon size={32} className="legal-icon" />
                <h1 style={{ margin: 0 }}>{heading(doc, lang)}</h1>
              </div>
              <div className="lang-switcher" style={{ display: "inline-flex", background: "#e2e8f0", borderRadius: "999px", padding: "3px", gap: "3px" }}>
                <button
                  type="button"
                  onClick={() => setLang("en")}
                  style={{
                    border: "none",
                    background: lang === "en" ? "var(--primary, #1b5e64)" : "transparent",
                    color: lang === "en" ? "#fff" : "#64748b",
                    padding: "6px 14px",
                    borderRadius: "999px",
                    cursor: "pointer",
                    fontWeight: 700,
                    fontSize: "0.82rem"
                  }}
                >
                  English
                </button>
                <button
                  type="button"
                  onClick={() => setLang("tr")}
                  style={{
                    border: "none",
                    background: lang === "tr" ? "var(--primary, #1b5e64)" : "transparent",
                    color: lang === "tr" ? "#fff" : "#64748b",
                    padding: "6px 14px",
                    borderRadius: "999px",
                    cursor: "pointer",
                    fontWeight: 700,
                    fontSize: "0.82rem"
                  }}
                >
                  Türkçe
                </button>
              </div>
            </div>
            <p className="legal-update" style={{ color: "#64748b", fontSize: "0.9rem" }}>
              {updateLine(doc, lang)}
            </p>
          </div>

          <article className="legal-body-text" style={{ marginTop: "2rem", lineHeight: "1.75", fontSize: "1rem" }}>
            {doc === "privacy" && <PrivacyContent lang={lang} />}
            {doc === "terms" && <TermsContent lang={lang} />}
            {doc === "about" && <AboutContent lang={lang} />}
            {doc === "mesafeli" && <MesafeliContent lang={lang} />}
            {doc === "teslimat" && <TeslimatContent lang={lang} />}
          </article>
        </section>
      </main>

      <StickyContactBar />
      <Footer />
    </div>
  );
}
