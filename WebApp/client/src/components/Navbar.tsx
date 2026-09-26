import { useState } from "react";
import { Link, useLocation } from "wouter";
import {
  Menu,
  X,
  ChevronDown,
  Sparkles,
  Utensils,
  Stethoscope,
  UsersRound,
  Building2,
  Zap,
  Clock3,
  MessageCircle,
  ShieldCheck,
  ArrowUpRight,
  MoveRight,
  Phone,
  Code2,
  Store,
  ArrowRight,
  Search,
  Loader2
} from "lucide-react";

export function Wordmark({ large = false }: { large?: boolean }) {
  return (
    <img
      className={large ? "brand-wordmark large" : "brand-wordmark"}
      src="/manus-storage/BooKi-transparent_2c86f190.png"
      alt="BooKi"
      width={large ? 190 : 132}
      height={large ? 90 : 63}
    />
  );
}

export function Navbar() {
  const [menuOpen, setMenuOpen] = useState(false);
  const [sectorDropdown, setSectorDropdown] = useState(false);
  const [featureDropdown, setFeatureDropdown] = useState(false);
  const [loginModalOpen, setLoginModalOpen] = useState(false);
  const [tenantSlug, setTenantSlug] = useState("");
  const [redirecting, setRedirecting] = useState(false);
  const [location] = useLocation();

  const appDomain = "bookiapp.kibusiness.co";

  const sanitizeSlug = (raw: string) => {
    let val = (raw || "").trim().toLowerCase();
    val = val.replace(/^https?:\/\//i, "");
    val = val.replace(/\/.*$/, "");
    val = val.replace(/:\d+$/, "");
    const pattern = appDomain.replace(".", "\\.");
    val = val.replace(new RegExp("[-.]" + pattern + "$", "i"), "");
    val = val.replace(/^@/, "");
    return val;
  };

  const handleLoginSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const cleanSlug = sanitizeSlug(tenantSlug);
    if (!cleanSlug) return;
    setRedirecting(true);
    if (/^[a-z0-9-]+$/.test(cleanSlug)) {
      window.location.href = `https://${cleanSlug}-${appDomain}/login`;
    } else {
      window.location.href = `https://${appDomain}/portal`;
    }
  };

  const previewSlug = sanitizeSlug(tenantSlug);
  const previewUrl = previewSlug && !previewSlug.includes("@") && !previewSlug.includes(".")
    ? `https://${previewSlug}-${appDomain}/login`
    : `https://...-${appDomain}/login`;

  const closeMenus = () => {
    setMenuOpen(false);
    setSectorDropdown(false);
    setFeatureDropdown(false);
  };

  const whatsappNumber = "905062505562";
  const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent("Merhaba BooKi ekibi, işletmem için online randevu/rezervasyon sistemi hakkında bilgi ve canlı demo almak istiyorum.")}`;

  return (
    <>
      {/* Top Announcement Bar */}
      <div className="announcement">
        <span>
          <Sparkles size={13} />
          <strong>%0 Komisyonlu Rezervasyon</strong> — Kurulumda ödeme güvencesiyle 15 dakikada yayında
        </span>
        <div className="announcement-links">
          <a
            href="https://randevuburada.kibusiness.co"
            target="_blank"
            rel="noopener noreferrer"
            className="announcement-software-link"
          >
            <Store size={13} /> RandevuBurada Pazaryeri <ArrowUpRight size={12} />
          </a>
          <a
            href="https://software.kibusiness.co"
            target="_blank"
            rel="noopener noreferrer"
            className="announcement-software-link"
          >
            <Code2 size={13} /> KiBusiness Yazılım Çözümleri <ArrowUpRight size={12} />
          </a>
          <a href={whatsappUrl} target="_blank" rel="noopener noreferrer">
            <MessageCircle size={13} /> WhatsApp Destek: 0506 250 55 62
          </a>
        </div>
      </div>

      {/* Main Header */}
      <header className="header main-site-header">
        <Link href="/" className="brand" onClick={closeMenus}>
          <Wordmark />
        </Link>

        {/* Mobile Toggle Button */}
        <button
          className="mobile-toggle"
          onClick={() => setMenuOpen(!menuOpen)}
          aria-label={menuOpen ? "Menüyü kapat" : "Menüyü aç"}
        >
          {menuOpen ? <X size={24} /> : <Menu size={24} />}
        </button>

        {/* Navigation Bar */}
        <nav className={`nav ${menuOpen ? "open" : ""}`}>
          <Link href="/" className={location === "/" ? "active" : ""} onClick={closeMenus}>
            Ana Sayfa
          </Link>

          {/* Sektörler Dropdown */}
          <div
            className="nav-dropdown-wrap"
            onMouseEnter={() => setSectorDropdown(true)}
            onMouseLeave={() => setSectorDropdown(false)}
          >
            <button
              type="button"
              className={`nav-dropdown-btn ${location.startsWith("/sektorler") ? "active" : ""}`}
              onClick={() => setSectorDropdown(!sectorDropdown)}
            >
              <span>Sektörler</span>
              <ChevronDown size={14} />
            </button>
            {sectorDropdown && (
              <div className="nav-dropdown-menu">
                <div className="dropdown-grid">
                  <Link href="/sektorler/restoran-masa-rezervasyon" onClick={closeMenus}>
                    <Utensils size={16} className="item-icon teal" />
                    <div>
                      <b>Restoran & Kafe</b>
                      <small>Masa ve kapasite yönetimi</small>
                    </div>
                  </Link>
                  <Link href="/sektorler/guzellik-salonu-randevu" onClick={closeMenus}>
                    <Sparkles size={16} className="item-icon peach" />
                    <div>
                      <b>Güzellik Salonu & Estetik</b>
                      <small>Uzman ve seans takibi</small>
                    </div>
                  </Link>
                  <Link href="/sektorler/kuafor-berber-randevu" onClick={closeMenus}>
                    <UsersRound size={16} className="item-icon amber" />
                    <div>
                      <b>Kuaför & Berber</b>
                      <small>Koltuk takvimi ve WhatsApp onay</small>
                    </div>
                  </Link>
                  <Link href="/sektorler/klinik-doktor-randevu" onClick={closeMenus}>
                    <Stethoscope size={16} className="item-icon blue" />
                    <div>
                      <b>Klinik & Doktor</b>
                      <small>Hasta takibi ve oda planlama</small>
                    </div>
                  </Link>
                  <Link href="/sektorler/spa-wellness-rezervasyon" onClick={closeMenus}>
                    <Sparkles size={16} className="item-icon lilac" />
                    <div>
                      <b>Spa & Masaj</b>
                      <small>Terapist ve oda eşleşmesi</small>
                    </div>
                  </Link>
                  <Link href="/sektorler/uzman-danisman-randevu" onClick={closeMenus}>
                    <Clock3 size={16} className="item-icon ice" />
                    <div>
                      <b>Psikolog & Danışman</b>
                      <small>Bireysel seans ve online takvim</small>
                    </div>
                  </Link>
                  <Link href="/sektorler/studyo-kurs-rezervasyon" onClick={closeMenus}>
                    <Zap size={16} className="item-icon coral" />
                    <div>
                      <b>Stüdyo & Pilates</b>
                      <small>Kontenjanlı grup dersleri</small>
                    </div>
                  </Link>
                  <Link href="/sektorler/coklu-sube-randevu-yonetimi" onClick={closeMenus}>
                    <Building2 size={16} className="item-icon slate" />
                    <div>
                      <b>Çoklu Şube & Zincir</b>
                      <small>Merkezi rapor ve yönetim</small>
                    </div>
                  </Link>
                </div>
              </div>
            )}
          </div>

          {/* Özellikler Dropdown */}
          <div
            className="nav-dropdown-wrap"
            onMouseEnter={() => setFeatureDropdown(true)}
            onMouseLeave={() => setFeatureDropdown(false)}
          >
            <button
              type="button"
              className={`nav-dropdown-btn ${location.startsWith("/ozellikler") ? "active" : ""}`}
              onClick={() => setFeatureDropdown(!featureDropdown)}
            >
              <span>Özellikler</span>
              <ChevronDown size={14} />
            </button>
            {featureDropdown && (
              <div className="nav-dropdown-menu narrow">
                <Link href="/ozellikler/whatsapp-randevu-onayi" onClick={closeMenus}>
                  <MessageCircle size={16} className="item-icon teal" />
                  <div>
                    <b>WhatsApp Onay & Hatırlatma</b>
                    <small>No-show'u %40 düşürün</small>
                  </div>
                </Link>
                <Link href="/ozellikler/no-show-onleme" onClick={closeMenus}>
                  <ShieldCheck size={16} className="item-icon coral" />
                  <div>
                    <b>No-Show Önleme & Waitlist</b>
                    <small>Boşalan yeri anında doldurun</small>
                  </div>
                </Link>
                <Link href="/ozellikler/ekip-ve-kaynak-takvimi" onClick={closeMenus}>
                  <UsersRound size={16} className="item-icon blue" />
                  <div>
                    <b>Akıllı Ekip & Kaynak Takvimi</b>
                    <small>Çakışmasız oda ve cihaz planı</small>
                  </div>
                </Link>
                <Link href="/ozellikler/musteri-yonetimi-crm" onClick={closeMenus}>
                  <Sparkles size={16} className="item-icon peach" />
                  <div>
                    <b>Müşteri Kartı & CRM Notları</b>
                    <small>Geçmiş kayıtlar ve sadakat</small>
                  </div>
                </Link>
                <Link href="/ozellikler/komisyonsuz-rezervasyon" onClick={closeMenus}>
                  <Zap size={16} className="item-icon amber" />
                  <div>
                    <b>%0 Komisyon Garantisi</b>
                    <small>Aracıya pay ödemeyin</small>
                  </div>
                </Link>
              </div>
            )}
          </div>

          <Link href="/karsilastirma" className={location === "/karsilastirma" ? "active" : ""} onClick={closeMenus}>
            <span className="badge-inline">20+ Rakip</span> Karşılaştırma
          </Link>

          <Link href="/fiyatlar" className={location === "/fiyatlar" ? "active" : ""} onClick={closeMenus}>
            Fiyatlar
          </Link>

          <Link href="/nasil-calisir" className={location === "/nasil-calisir" ? "active" : ""} onClick={closeMenus}>
            Nasıl Çalışır?
          </Link>

          <Link href="/iletisim" className={location === "/iletisim" ? "active" : ""} onClick={closeMenus}>
            İletişim
          </Link>

          <a
            href="https://randevuburada.kibusiness.co"
            target="_blank"
            rel="noopener noreferrer"
            className="nav-link-pazaryeri"
            style={{
              display: "inline-flex",
              alignItems: "center",
              gap: "5px",
              padding: "5px 10px",
              borderRadius: "6px",
              background: "rgba(46, 153, 149, 0.12)",
              color: "var(--teal)",
              fontWeight: 700,
              fontSize: "12px",
              border: "1px solid rgba(46, 153, 149, 0.3)",
              whiteSpace: "nowrap"
            }}
          >
            <Store size={13} /> RandevuBurada Pazaryeri <ArrowUpRight size={11} />
          </a>

          {/* CTA Buttons */}
          <div className="nav-cta">
            <a
              className="login-link"
              href={`https://${appDomain}/portal`}
              onClick={(e) => {
                e.preventDefault();
                setLoginModalOpen(true);
              }}
            >
              Giriş yap
            </a>
            <a
              href={whatsappUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="button button-whatsapp compact"
            >
              <MessageCircle size={14} /> Canlı Demo
            </a>
          </div>
        </nav>
      </header>

      {/* Tenant Login Modal */}
      {loginModalOpen && (
        <div
          style={{
            position: "fixed",
            inset: 0,
            zIndex: 99999,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            padding: "16px",
          }}
        >
          <div
            onClick={() => setLoginModalOpen(false)}
            style={{
              position: "fixed",
              inset: 0,
              backgroundColor: "rgba(15, 23, 42, 0.65)",
              backdropFilter: "blur(4px)",
            }}
          />
          <div
            style={{
              position: "relative",
              backgroundColor: "#ffffff",
              borderRadius: "20px",
              width: "100%",
              maxWidth: "440px",
              padding: "2.2rem 2rem",
              boxShadow: "0 20px 40px rgba(0, 0, 0, 0.2)",
              zIndex: 1,
              textAlign: "left",
              color: "#0f172a",
            }}
          >
            <button
              type="button"
              onClick={() => setLoginModalOpen(false)}
              style={{
                position: "absolute",
                top: "14px",
                right: "16px",
                background: "none",
                border: "none",
                cursor: "pointer",
                color: "#94a3b8",
                padding: "4px",
              }}
              aria-label="Kapat"
            >
              <X size={20} />
            </button>

            <div
              style={{
                display: "inline-flex",
                alignItems: "center",
                gap: "6px",
                fontSize: "12px",
                fontWeight: 700,
                background: "#ccfbf1",
                color: "#0f766e",
                padding: "4px 12px",
                borderRadius: "999px",
                marginBottom: "12px",
              }}
            >
              <Store size={13} /> BooKi İşletme Girişi
            </div>

            <h3 style={{ fontSize: "1.25rem", fontWeight: 800, color: "#0f172a", marginBottom: "6px", lineHeight: 1.3 }}>
              Yönetim Panelinize Giriş Yapın
            </h3>
            <p style={{ fontSize: "0.85rem", color: "#64748b", marginBottom: "1.25rem", lineHeight: 1.45 }}>
              Superadmin'de belirlenen işletme kullanıcı adınızı (subdomain) girerek işletmenize özel giriş ekranına yönlenin.
            </p>

            <form onSubmit={handleLoginSubmit}>
              <div style={{ marginBottom: "16px" }}>
                <label style={{ display: "block", fontSize: "0.82rem", fontWeight: 700, color: "#0f172a", marginBottom: "6px" }}>
                  İşletme Kullanıcı Adı (Subdomain)
                </label>
                <div style={{ position: "relative", display: "flex", alignItems: "center" }}>
                  <Store size={16} style={{ position: "absolute", left: "12px", color: "#94a3b8" }} />
                  <input
                    type="text"
                    value={tenantSlug}
                    onChange={(e) => setTenantSlug(e.target.value)}
                    placeholder="isletme-kullanici-adi"
                    autoFocus
                    required
                    style={{
                      width: "100%",
                      padding: "10px 14px 10px 38px",
                      border: "1.5px solid #cbd5e1",
                      borderRadius: "10px",
                      fontSize: "0.95rem",
                      fontWeight: 600,
                      color: "#0f172a",
                      backgroundColor: "#f8fafc",
                      outline: "none",
                    }}
                  />
                </div>
                <div
                  style={{
                    marginTop: "8px",
                    fontSize: "0.78rem",
                    color: "#64748b",
                    backgroundColor: "#f8fafc",
                    padding: "6px 10px",
                    borderRadius: "6px",
                    border: "1px solid #e2e8f0",
                    wordBreak: "break-all",
                  }}
                >
                  Adres: <strong style={{ color: "#0f766e" }}>{previewUrl}</strong>
                </div>
              </div>

              <button
                type="submit"
                disabled={redirecting}
                style={{
                  width: "100%",
                  padding: "12px",
                  background: "linear-gradient(135deg, #0f766e 0%, #115e59 100%)",
                  color: "#ffffff",
                  border: "none",
                  borderRadius: "10px",
                  fontSize: "0.95rem",
                  fontWeight: 700,
                  cursor: redirecting ? "not-allowed" : "pointer",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  gap: "8px",
                  boxShadow: "0 4px 12px rgba(15, 118, 110, 0.25)",
                }}
              >
                {redirecting ? (
                  <>
                    <span>Yönlendiriliyor...</span>
                    <Loader2 size={16} className="animate-spin" />
                  </>
                ) : (
                  <>
                    <span>Giriş Ekranına Git</span>
                    <ArrowRight size={16} />
                  </>
                )}
              </button>
            </form>

            <div style={{ marginTop: "16px", textAlign: "center", fontSize: "0.82rem" }}>
              <a
                href={`https://${appDomain}/portal`}
                style={{ color: "#0f766e", fontWeight: 600, textDecoration: "none" }}
              >
                <Search size={13} style={{ display: "inline", marginRight: "4px", verticalAlign: "middle" }} />
                İşletme adınızı hatırlamıyor musunuz? E-posta ile bulun
              </a>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
