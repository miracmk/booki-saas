import { Link } from "wouter";
import { Wordmark } from "./Navbar";
import {
  ArrowUpRight,
  Phone,
  MessageCircle,
  Mail,
  ShieldCheck,
  CheckCircle2,
  Sparkles,
  Code2,
  Globe2,
  Store,
  Instagram,
  Linkedin,
  MapPin
} from "lucide-react";

export function Footer() {
  const whatsappNumber = "905062505562";
  const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent("Merhaba BooKi ekibi, randevu ve rezervasyon sistemi hakkında görüşmek istiyorum.")}`;

  return (
    <footer className="footer mega-footer">
      {/* Pre-Footer Funnel CTA Strip */}
      <div className="pre-footer-banner">
        <div className="pre-footer-content">
          <div>
            <span className="badge-light">
              <Sparkles size={13} /> KURULUMDA ÖDEME GÜVENCESİ
            </span>
            <h3>
              İşletmenizi 15 dakikada <em>akışa geçirelim.</em>
            </h3>
            <p>
              Kredi kartı girmeden görüşelim, demo yapalım. Sisteminizi kurup onayınızı aldığımızda ödemenizi yapın.
            </p>
          </div>
          <div className="pre-footer-actions">
            <a href={whatsappUrl} target="_blank" rel="noopener noreferrer" className="button button-whatsapp">
              <MessageCircle size={17} /> WhatsApp ile Konuşalım
            </a>
            <a href="tel:+905062505562" className="button button-dark">
              <Phone size={16} /> 0506 250 55 62
            </a>
          </div>
        </div>
      </div>

      {/* Main Footer Links Matrix */}
      <div className="footer-top">
        <div className="footer-brand-col">
          <Link href="/" className="brand">
            <Wordmark />
          </Link>
          <p className="footer-desc">
            Restoran, güzellik salonu, klinik, spa ve uzman işletmeleri için %0 komisyonlu akıllı randevu ve masa yönetim platformu.
          </p>
          <div className="footer-contact-info">
            <div className="contact-line" style={{ alignItems: "flex-start" }}>
              <MapPin size={14} style={{ flexShrink: 0, marginTop: "3px" }} />
              <span>Çekirge Mh. Süleyman Sk. No 29 Osmangazi Bursa</span>
            </div>
            <a href="tel:+905062505562" className="contact-line">
              <Phone size={14} /> <span>0506 250 55 62</span>
            </a>
            <a href="mailto:hello@kibusiness.co" className="contact-line">
              <Mail size={14} /> <span>hello@kibusiness.co</span>
            </a>
            <a href={whatsappUrl} target="_blank" rel="noopener noreferrer" className="contact-line">
              <MessageCircle size={14} /> <span>WhatsApp Destek: 0506 250 55 62</span>
            </a>
            <a
              href="https://instagram.com/booki.reservation"
              target="_blank"
              rel="noopener noreferrer"
              className="contact-line social-link"
            >
              <Instagram size={14} /> <span>@booki.reservation</span> <ArrowUpRight size={12} />
            </a>
          </div>
          <div className="trust-pills">
            <span><CheckCircle2 size={13} /> %0 Komisyon</span>
            <span><CheckCircle2 size={13} /> Kurulumda Ödeme</span>
            <span><CheckCircle2 size={13} /> KVKK Uyumlu</span>
          </div>
        </div>

        <div className="footer-links-grid">
          {/* Column 1: Sektörel Çözümler */}
          <div className="footer-col">
            <b>Sektörler</b>
            <Link href="/sektorler/restoran-masa-rezervasyon">Restoran & Kafe</Link>
            <Link href="/sektorler/guzellik-salonu-randevu">Güzellik Salonu & Estetik</Link>
            <Link href="/sektorler/kuafor-berber-randevu">Kuaför & Berber</Link>
            <Link href="/sektorler/klinik-doktor-randevu">Klinik & Hekim</Link>
            <Link href="/sektorler/spa-wellness-rezervasyon">Spa & Masaj</Link>
            <Link href="/sektorler/uzman-danisman-randevu">Psikolog & Danışman</Link>
            <Link href="/sektorler/studyo-kurs-rezervasyon">Stüdyo & Pilates</Link>
            <Link href="/sektorler/coklu-sube-randevu-yonetimi">Çoklu Şube & Zincir</Link>
          </div>

          {/* Column 2: Özellikler */}
          <div className="footer-col">
            <b>Özellikler</b>
            <Link href="/ozellikler/whatsapp-randevu-onayi">WhatsApp Onay & Teyit</Link>
            <Link href="/ozellikler/no-show-onleme">No-Show Önleme & Waitlist</Link>
            <Link href="/ozellikler/ekip-ve-kaynak-takvimi">Ekip & Kaynak Takvimi</Link>
            <Link href="/ozellikler/musteri-yonetimi-crm">Müşteri Kartı & CRM</Link>
            <Link href="/ozellikler/komisyonsuz-rezervasyon">%0 Komisyon Altyapısı</Link>
            <Link href="/karsilastirma">20+ Rakip Karşılaştırması</Link>
          </div>

          {/* Column 3: RandevuBurada Pazaryeri */}
          <div className="footer-col">
            <b>✦ RandevuBurada Pazaryeri</b>
            <a
              href="https://randevuburada.kibusiness.co"
              target="_blank"
              rel="noopener noreferrer"
              style={{
                display: "inline-flex",
                alignItems: "center",
                gap: "5px",
                color: "var(--teal)",
                fontWeight: 700,
                fontSize: "12px",
                marginBottom: "2px"
              }}
            >
              <Store size={14} /> randevuburada.kibusiness.co <ArrowUpRight size={11} />
            </a>
            <span style={{ fontSize: "11px", color: "var(--muted)", lineHeight: 1.5, marginBottom: "4px" }}>
              BooKi işletmeleri RandevuBurada ortak pazaryerinde listelenerek yeni müşterilere komisyonsuz ulaşır.
            </span>
            <a href="https://randevuburada.kibusiness.co" target="_blank" rel="noopener noreferrer">
              Kuaför &amp; Berber Randevusu <ArrowUpRight size={11} />
            </a>
            <a href="https://randevuburada.kibusiness.co" target="_blank" rel="noopener noreferrer">
              Güzellik Salonu Randevusu <ArrowUpRight size={11} />
            </a>
            <a href="https://randevuburada.kibusiness.co" target="_blank" rel="noopener noreferrer">
              Restoran &amp; Kafe Rezervasyonu <ArrowUpRight size={11} />
            </a>
            <a href="https://randevuburada.kibusiness.co" target="_blank" rel="noopener noreferrer">
              Klinik &amp; Hekim Randevusu <ArrowUpRight size={11} />
            </a>
            <a href="https://randevuburada.kibusiness.co" target="_blank" rel="noopener noreferrer" style={{ fontWeight: 700, color: "var(--teal)" }}>
              Tüm Kategorileri Keşfet <ArrowUpRight size={11} />
            </a>
          </div>

          {/* Column 4: Hızlı Bağlantılar & Kurumsal */}
          <div className="footer-col">
            <b>Hızlı Bağlantılar</b>
            <Link href="/fiyatlar">Fiyatlar &amp; Paketler</Link>
            <Link href="/karsilastirma">20+ Rakip Karşılaştırması</Link>
            <Link href="/nasil-calisir">Nasıl Çalışır?</Link>
            <Link href="/iletisim">Canlı Demo &amp; İletişim</Link>
            <a href="https://bookiapp.kibusiness.co/portal" target="_blank" rel="noopener noreferrer">
              İşletme Girişi <ArrowUpRight size={11} />
            </a>
            <a href="https://software.kibusiness.co" target="_blank" rel="noopener noreferrer">
              KiBusiness Yazılım <ArrowUpRight size={11} />
            </a>
            <Link href="/privacy">Gizlilik Politikası (KVKK)</Link>
            <Link href="/terms">Kullanım Koşulları</Link>
            <Link href="/mesafeli-satis">Mesafeli Satış Sözleşmesi</Link>
            <Link href="/teslimat-iade">Teslimat &amp; İade</Link>
            <Link href="/hakkimizda">Hakkımızda</Link>
          </div>
        </div>
      </div>

      {/* Payment Trust Strip */}
      <div className="footer-payments">
        <div className="footer-payments-meta">
          <span className="footer-payments-label">
            <ShieldCheck size={15} /> Güvenli Ödeme
          </span>
          <span className="footer-payments-sub">
            Randevularınız iyzico altyapısı ile 3-D Secure korumalı olarak işlenir.
          </span>
        </div>
        <div className="footer-payments-logos">
          <img src="/images/payment-logos.svg" alt="Visa, Mastercard ve Troy ile iyzico üzerinden güvenli ödeme" className="payment-band" loading="lazy" />
          <img src="/images/iyzico_ile_ode.svg" alt="iyzico ile öde" className="payment-badge" loading="lazy" />
        </div>
      </div>

      {/* Footer Bottom */}
      <div className="footer-bottom">
        <span>© 2026 BooKi — Ki Software (Ki Business Solutions). Tüm hakları saklıdır.</span>
        <div className="footer-legal-links">
          <a href="https://booki.kibusiness.co">booki.kibusiness.co</a>
          <span>·</span>
          <a href="https://randevuburada.kibusiness.co" target="_blank" rel="noopener noreferrer" style={{ color: "var(--teal)", fontWeight: 600 }}>
            RandevuBurada Pazaryeri
          </a>
          <span>·</span>
          <Link href="/privacy">Privacy Policy</Link>
          <span>·</span>
          <Link href="/terms">Terms of Service</Link>
          <span>·</span>
          <Link href="/gizlilik">KVKK</Link>
          <span>·</span>
          <Link href="/iletisim">İletişim</Link>
        </div>
      </div>
    </footer>
  );
}
