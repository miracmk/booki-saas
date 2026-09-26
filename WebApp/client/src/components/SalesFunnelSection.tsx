import { useState, type FormEvent } from "react";
import { trpc } from "@/lib/trpc";
import {
  Phone,
  MessageCircle,
  CalendarDays,
  ShieldCheck,
  Zap,
  Check,
  MoveRight,
  ArrowUpRight,
  Sparkles,
  Users
} from "lucide-react";

export function SalesFunnelSection({
  defaultSector = "",
  title = "İyi iş, daha iyi bir akışla başlar.",
  subtitle = "15 dakikada sisteminizi kuralım, ödemeyi kurulumda güvenle yapın."
}: {
  defaultSector?: string;
  title?: string;
  subtitle?: string;
}) {
  const [demoSent, setDemoSent] = useState(false);
  const [demoError, setDemoError] = useState("");
  const demoRequest = trpc.demo.request.useMutation();

  const whatsappNumber = "905062505562";
  const whatsappUrl = `https://wa.me/${whatsappNumber}?text=` + encodeURIComponent(`Merhaba BooKi ekibi, ${defaultSector || "işletmem"} için randevu/rezervasyon sistemi demosu ve kurulumu hakkında bilgi almak istiyorum.`);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setDemoError("");
    const form = event.currentTarget;
    const formData = new FormData(form);

    try {
      await demoRequest.mutateAsync({
        name: String(formData.get("name") || ""),
        email: String(formData.get("email") || ""),
        phone: String(formData.get("phone") || ""),
        preferredContactTime: String(formData.get("preferredContactTime") || ""),
        businessType: String(formData.get("businessType") || defaultSector || "Genel"),
      });
      setDemoSent(true);
      if (typeof (window as any).trackBooKiConversion === "function") {
        (window as any).trackBooKiConversion("Form_Lead", "SalesFunnel");
      }
      form.reset();
    } catch {
      setDemoError("Talebiniz iletilemedi. Lütfen doğrudan WhatsApp üzerinden bize yazın veya telefonla arayın.");
    }
  };

  return (
    <section className="sales-funnel-section" id="demo-section">
      <div className="funnel-container">
        {/* Step-by-Step Transparency Cards */}
        <div className="funnel-steps-header">
          <div className="section-label light">
            <span /> NASIL ÇALIŞIRIZ?
          </div>
          <h2>{title}</h2>
          <p>{subtitle}</p>

          <div className="funnel-steps-grid">
            <div className="step-card">
              <span className="step-num">01</span>
              <div className="step-icon teal">
                <Phone size={20} />
              </div>
              <h4>İletişim & Demo Talebi</h4>
              <p>WhatsApp'tan yazın, arayın veya formu doldurun. Uzmanımız işletmenizi dinlesin.</p>
            </div>

            <div className="step-card">
              <span className="step-num">02</span>
              <div className="step-icon peach">
                <CalendarDays size={20} />
              </div>
              <h4>15 Dk Canlı Görüşme</h4>
              <p>Ekran paylaşımıyla işletmenize özel salon/uzman takvimini canlı test edelim.</p>
            </div>

            <div className="step-card highlight">
              <span className="step-num">03</span>
              <div className="step-icon amber">
                <ShieldCheck size={20} />
              </div>
              <h4>Kurulumda Ödeme & Onay</h4>
              <p>Sisteminiz tam hazır olduğunda ödemenizi yapın. Kredi kartı riski yok.</p>
            </div>

            <div className="step-card">
              <span className="step-num">04</span>
              <div className="step-icon blue">
                <Sparkles size={20} />
              </div>
              <h4>%0 Komisyonla Büyüyün</h4>
              <p>Müşterileriniz online randevu alsın, 7/24 WhatsApp destekle yanınızdayız.</p>
            </div>
          </div>
        </div>

        {/* Lead Capture Box / Direct WhatsApp */}
        <div className="funnel-action-box">
          <div className="action-box-left">
            <h3>Hemen Konuşalım</h3>
            <p>
              Vakit kaybetmeden doğrudan müşteri temsilcimizle WhatsApp'tan mesajlaşabilir veya canlı demo randevusu oluşturabilirsiniz.
            </p>

            <div className="direct-channels">
              <a
                href={whatsappUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="channel-btn whatsapp"
                onClick={() => {
                  if (typeof (window as any).trackBooKiConversion === "function") {
                    (window as any).trackBooKiConversion("WhatsApp", "Funnel Section");
                  }
                }}
              >
                <MessageCircle size={20} />
                <div>
                  <b>WhatsApp ile Anında Başla</b>
                  <small>0506 250 55 62 · Ortalama yanıt süresi 2 dakika</small>
                </div>
                <ArrowUpRight size={16} />
              </a>

              <a
                href="tel:+905062505562"
                className="channel-btn phone"
                onClick={() => {
                  if (typeof (window as any).trackBooKiConversion === "function") {
                    (window as any).trackBooKiConversion("Call", "Funnel Section");
                  }
                }}
              >
                <Phone size={20} />
                <div>
                  <b>Doğrudan Telefonla Ara</b>
                  <small>0506 250 55 62 (Hafta içi 09:00 - 19:00)</small>
                </div>
                <ArrowUpRight size={16} />
              </a>
            </div>

            <div className="funnel-guarantees">
              <span><Check size={14} /> Kredi kartı zorunluluğu yok</span>
              <span><Check size={14} /> %0 Rezervasyon komisyonu</span>
              <span><Check size={14} /> 15 Dakikada hızlı kurulum</span>
            </div>
          </div>

          <div className="action-box-right">
            {demoSent ? (
              <div className="funnel-success" role="status" aria-live="polite">
                <div className="success-icon">
                  <Check size={28} />
                </div>
                <h4>Talebiniz Başarıyla Alındı!</h4>
                <p>
                  Müşteri temsilcimiz seçtiğiniz iletişim saatinde sizinle iletişime geçecektir.
                </p>
                <a href={whatsappUrl} target="_blank" rel="noopener noreferrer" className="button button-whatsapp">
                  <MessageCircle size={16} /> WhatsApp'tan Hemen Yaz
                </a>
              </div>
            ) : (
              <form className="funnel-form" onSubmit={handleSubmit}>
                <h4>Demo & Görüşme Formu</h4>
                <label>
                  <span>Adınız Soyadınız *</span>
                  <input name="name" type="text" placeholder="Ad Soyad..." required />
                </label>

                <label>
                  <span>İşletme E-postası *</span>
                  <input name="email" type="email" placeholder="ornek@isletmeniz.com" required />
                </label>

                <label>
                  <span>Telefon Numaranız *</span>
                  <input name="phone" type="tel" placeholder="05xx xxx xx xx" required />
                </label>

                <div className="form-row-2">
                  <label>
                    <span>İşletme Türü</span>
                    <select name="businessType" defaultValue={defaultSector || ""}>
                      <option value="" disabled>Seçin...</option>
                      <option value="Restoran & Kafe">Restoran & Kafe</option>
                      <option value="Güzellik Salonu & Kuaför">Güzellik Salonu & Kuaför</option>
                      <option value="Klinik & Sağlık">Klinik & Sağlık</option>
                      <option value="Spa & Wellness">Spa & Wellness</option>
                      <option value="Psikolog & Uzman">Psikolog & Uzman</option>
                      <option value="Stüdyo & Kurs">Stüdyo & Kurs</option>
                      <option value="Çoklu Şube">Çoklu Şube</option>
                      <option value="Diğer">Diğer</option>
                    </select>
                  </label>

                  <label>
                    <span>Arama Zamanı</span>
                    <select name="preferredContactTime" defaultValue="Hemen / Fark etmez">
                      <option>Hemen / Fark etmez</option>
                      <option>09:00 - 12:00</option>
                      <option>12:00 - 15:00</option>
                      <option>15:00 - 18:00</option>
                      <option>18:00 Sonrası</option>
                    </select>
                  </label>
                </div>

                <button type="submit" className="button button-primary" disabled={demoRequest.isPending}>
                  {demoRequest.isPending ? "İletiliyor..." : <>Demo Randevusu Oluştur <MoveRight size={16} /></>}
                </button>

                {demoError && <p className="funnel-error-msg">{demoError}</p>}
              </form>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}
