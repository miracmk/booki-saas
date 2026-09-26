import { Phone, MessageCircle, ArrowUpRight, Sparkles } from "lucide-react";

export function StickyContactBar() {
  const whatsappNumber = "905062505562";
  const whatsappMessage = encodeURIComponent("Merhaba BooKi ekibi, işletmem için online randevu/rezervasyon sistemi hakkında bilgi ve canlı demo almak istiyorum.");
  const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${whatsappMessage}`;
  const phoneUrl = `tel:+${whatsappNumber}`;

  return (
    <aside className="sticky-contact-widget" aria-label="Hızlı İletişim ve Destek">
      {/* Floating Action Buttons */}
      <div className="floating-action-cluster">
        {/* Quick WhatsApp Button */}
        <a
          href={whatsappUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="floating-btn whatsapp-btn"
          aria-label="WhatsApp üzerinden hemen yazın ve canlı demo isteyin"
          onClick={() => {
            if (typeof (window as any).trackBooKiConversion === "function") {
              (window as any).trackBooKiConversion("WhatsApp", "Floating Widget");
            }
          }}
        >
          <span className="btn-icon">
            <MessageCircle size={20} />
          </span>
          <span className="btn-text">
            <b>WhatsApp'tan Yazın</b>
            <small>0506 250 55 62</small>
          </span>
          <span className="online-indicator" aria-hidden="true" />
        </a>

        {/* Quick Phone Call Button */}
        <a
          href={phoneUrl}
          className="floating-btn phone-btn"
          aria-label="Müşteri temsilcimizi doğrudan arayın"
          onClick={() => {
            if (typeof (window as any).trackBooKiConversion === "function") {
              (window as any).trackBooKiConversion("Call", "Floating Widget");
            }
          }}
        >
          <span className="btn-icon">
            <Phone size={18} />
          </span>
          <span className="btn-text">
            <b>Hemen Arayın</b>
            <small>0506 250 55 62</small>
          </span>
        </a>
      </div>

      {/* Floating Bottom Quick Bar for Mobile */}
      <div className="mobile-bottom-funnel-bar">
        <a href={whatsappUrl} target="_blank" rel="noopener noreferrer" className="mobile-funnel-btn whatsapp">
          <MessageCircle size={17} />
          <span>WhatsApp Demo</span>
        </a>
        <a href="/nasil-calisir" className="mobile-funnel-btn trial">
          <Sparkles size={16} />
          <span>Nasıl Çalışır?</span>
        </a>
        <a href={phoneUrl} className="mobile-funnel-btn call">
          <Phone size={16} />
          <span>Ara</span>
        </a>
      </div>
    </aside>
  );
}
