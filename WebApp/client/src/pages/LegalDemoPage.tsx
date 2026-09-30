import React, { useState, useEffect } from "react";
import { Link } from "wouter";
import {
  ShieldCheck,
  FileText,
  Search,
  CheckCircle,
  Eye,
  PenTool,
  Lock,
  ChevronRight,
  ExternalLink,
  BookOpen,
  Stethoscope,
  Building,
  Car,
  Sparkles,
  Building2,
  Calendar,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import { LegalGateModal } from "@/components/legal/LegalGateModal";
import { BookingContextData, SignedContractResponse } from "@shared/legalTypes";

export const LegalDemoPage: React.FC = () => {
  const [templates, setTemplates] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [activeTemplate, setActiveTemplate] = useState<any | null>(null);

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [signedResults, setSignedResults] = useState<SignedContractResponse[]>([]);

  // Demo Booking Context
  const sampleBookingContext: BookingContextData = {
    tenantId: "TENANT_CLINIC_01",
    tenantName: "BooKi Sağlık & Estetik Merkezi",
    tenantLegalName: "BooKi Sağlık ve Turizm Hizmetleri A.Ş.",
    tenantTaxId: "9876543210",
    tenantMersis: "098765432100001",
    tenantAddress: "Bağdat Caddesi No: 120 Kadıköy / İstanbul",
    tenantPhone: "+90 216 444 88 99",

    bookingId: "REZ-2026-9901",
    serviceName: "Katarakt / Lazer Cerrahi ve Cilt Yenileme Konsültasyonu",
    servicePrice: 3500,
    depositAmount: 1000,
    hasPrepayment: true,
    appointmentDateTime: "2026-10-15T10:30:00Z",
    staffName: "Uzm. Dr. Aylin Kaya",

    cancellationDeadlineHours: 24,
    penaltyRatePercent: 50,

    customerId: "CUST-DEMO-77",
    customerFullName: "Mehmet Yılmaz",
    customerNationalId: "12345678901",
    customerPhone: "+90 532 999 88 77",
    customerEmail: "mehmet.yilmaz@example.com",

    complicationsList: [
      "İşlem bölgesinde geçici kızarıklık, hafif ödem ve hassasiyet",
      "Gözde geçici batma ve ışık parlaması hissi",
      "Önerilen koruyucu ilaç ve damlaların aksatılması durumunda enfeksiyon riski",
    ],
    propertyParcelAddress: "Kadıköy / Caddebostan Ada: 102 Parsel: 4",
    vehicleVinPlate: "34 BKI 2026",
  };

  useEffect(() => {
    fetch("/api/legal/templates")
      .then((res) => res.json())
      .then((data) => {
        if (data.templates) {
          setTemplates(data.templates);
          if (data.templates.length > 0) {
            setActiveTemplate(data.templates[0]);
          }
        }
        setLoading(false);
      })
      .catch((err) => {
        console.error("Şablonlar yüklenemedi:", err);
        setLoading(false);
      });
  }, []);

  const filteredTemplates = templates.filter((tpl) => {
    const matchesSearch =
      tpl.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      tpl.docType.toLowerCase().includes(searchQuery.toLowerCase()) ||
      (tpl.sourceReference && tpl.sourceReference.toLowerCase().includes(searchQuery.toLowerCase()));

    if (selectedCategory === "all") return matchesSearch;
    if (selectedCategory === "saglik") {
      return matchesSearch && (tpl.sectorFamily === "health_clinical" || tpl.category === "saglik_cerrahi" || tpl.category === "dis_hekimligi");
    }
    if (selectedCategory === "estetik") {
      return matchesSearch && (tpl.sectorFamily === "beauty_wellness" || tpl.category === "dermatoloji_estetik");
    }
    if (selectedCategory === "turizm") {
      return matchesSearch && (tpl.sectorFamily === "hospitality" || tpl.category === "konaklama_turizm");
    }
    if (selectedCategory === "otomotiv") {
      return matchesSearch && (tpl.sectorFamily === "automotive" || tpl.category === "otomotiv_servis");
    }
    return matchesSearch;
  });

  return (
    <div className="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
      <div className="max-w-7xl mx-auto space-y-8">
        {/* Header */}
        <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-8 text-white shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
          <div className="space-y-2">
            <div className="flex items-center gap-2">
              <Badge className="bg-emerald-500/20 text-emerald-300 border-emerald-500/30 gap-1.5 py-1 px-3">
                <ShieldCheck className="w-4 h-4" /> Ceza-Geçirmez Hukuk & Onam Kütüphanesi
              </Badge>
              <Badge className="bg-blue-500/20 text-blue-300 border-blue-500/30 py-1 px-3">
                HMK m. 199 / TBK m. 20-25 / KVKK m. 6
              </Badge>
            </div>
            <h1 className="text-3xl font-extrabold tracking-tight">
              Sektörel Aydınlatılmış Onam & Yasal Sözleşme Kataloğu
            </h1>
            <p className="text-slate-300 text-sm max-w-2xl leading-relaxed">
              TOD, İDO, Türk Dermatoloji Derneği, TÜRSAB, Baia Hotels, AvEvrak, Lexpera, Türk Yoğun Bakım Derneği ve
              resmi mevzuat standartlarına tam uyumlu, kriptografik zaman damgalı sözleşme ve rıza formları.
            </p>
          </div>

          <div className="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <Button
              onClick={() => setIsModalOpen(true)}
              className="bg-emerald-600 hover:bg-emerald-700 text-white gap-2 font-medium shadow-lg shadow-emerald-900/30"
            >
              <PenTool className="w-4 h-4" /> Dijital İmza Kapısını Test Et
            </Button>
            <Link href="/">
              <Button variant="outline" className="border-slate-700 text-slate-200 hover:bg-slate-800">
                Ana Sayfa
              </Button>
            </Link>
          </div>
        </div>

        {/* Categories & Search */}
        <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
          <Tabs value={selectedCategory} onValueChange={setSelectedCategory} className="w-full md:w-auto">
            <TabsList className="bg-white border border-slate-200 p-1 shadow-sm">
              <TabsTrigger value="all" className="gap-1.5 text-xs font-medium">Tümü ({templates.length})</TabsTrigger>
              <TabsTrigger value="saglik" className="gap-1.5 text-xs font-medium">
                <Stethoscope className="w-3.5 h-3.5" /> Sağlık, Tıp & Diş
              </TabsTrigger>
              <TabsTrigger value="estetik" className="gap-1.5 text-xs font-medium">
                <Sparkles className="w-3.5 h-3.5" /> Dermatoloji & Estetik
              </TabsTrigger>
              <TabsTrigger value="turizm" className="gap-1.5 text-xs font-medium">
                <Building className="w-3.5 h-3.5" /> Konaklama & Turizm
              </TabsTrigger>
              <TabsTrigger value="otomotiv" className="gap-1.5 text-xs font-medium">
                <Car className="w-3.5 h-3.5" /> Otomotiv Servis
              </TabsTrigger>
            </TabsList>
          </Tabs>

          <div className="relative w-full md:w-80">
            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
            <Input
              placeholder="Şablon veya kanun maddesi ara..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-9 bg-white border-slate-200 shadow-sm text-sm"
            />
          </div>
        </div>

        {/* Master Catalog Layout */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          {/* Templates List */}
          <div className="lg:col-span-5 space-y-3">
            <h2 className="text-sm font-semibold text-slate-500 uppercase tracking-wider px-1">
              Kayıtlı Sözleşme & Onam Şablonları ({filteredTemplates.length})
            </h2>

            {loading ? (
              <div className="py-12 text-center text-slate-500">Şablonlar yükleniyor...</div>
            ) : filteredTemplates.length === 0 ? (
              <div className="py-12 text-center text-slate-500 bg-white rounded-xl border border-dashed border-slate-300">
                Aramanızla eşleşen şablon bulunamadı.
              </div>
            ) : (
              <div className="space-y-2.5 max-h-[750px] overflow-y-auto pr-1">
                {filteredTemplates.map((tpl) => {
                  const isSelected = activeTemplate?.id === tpl.id;
                  return (
                    <div
                      key={tpl.id}
                      onClick={() => setActiveTemplate(tpl)}
                      className={`p-4 rounded-xl border cursor-pointer transition-all ${
                        isSelected
                          ? "bg-emerald-50/50 border-emerald-500 ring-2 ring-emerald-500/20 shadow-sm"
                          : "bg-white border-slate-200 hover:border-slate-300 hover:shadow-sm"
                      }`}
                    >
                      <div className="flex items-start justify-between gap-2 mb-1.5">
                        <Badge variant="outline" className="text-[10px] uppercase font-mono tracking-wider">
                          {tpl.docType}
                        </Badge>
                        {tpl.requiresOtp && (
                          <Badge className="bg-amber-100 text-amber-800 text-[10px] hover:bg-amber-100">
                            SMS OTP 2FA
                          </Badge>
                        )}
                        {tpl.requiresGps && (
                          <Badge className="bg-blue-100 text-blue-800 text-[10px] hover:bg-blue-100">
                            GPS Damgalı
                          </Badge>
                        )}
                      </div>
                      <h3 className="font-semibold text-sm text-slate-900 line-clamp-1">{tpl.title}</h3>
                      {tpl.sourceReference && (
                        <p className="text-xs text-emerald-700 mt-1 flex items-center gap-1">
                          <BookOpen className="w-3 h-3 shrink-0" />
                          <span className="truncate">{tpl.sourceReference}</span>
                        </p>
                      )}
                      <p className="text-xs text-slate-500 line-clamp-2 mt-1">{tpl.description}</p>
                    </div>
                  );
                })}
              </div>
            )}
          </div>

          {/* Template Detail & Preview */}
          <div className="lg:col-span-7">
            {activeTemplate ? (
              <Card className="shadow-lg border-slate-200 bg-white sticky top-6">
                <CardHeader className="border-b border-slate-100 pb-4">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <Badge className="bg-slate-900 text-white font-mono text-xs">{activeTemplate.docType}</Badge>
                    <div className="flex items-center gap-2">
                      <Badge variant="outline" className="text-xs text-slate-600">
                        Sektör: {activeTemplate.sectorFamily}
                      </Badge>
                      <Badge variant="outline" className="text-xs text-emerald-700 border-emerald-300 bg-emerald-50">
                        {activeTemplate.signatureLevel || "Biyometrik Kanvas"}
                      </Badge>
                    </div>
                  </div>
                  <CardTitle className="text-lg text-slate-900 mt-2">{activeTemplate.title}</CardTitle>
                  {activeTemplate.legalBasis && (
                    <CardDescription className="text-xs text-amber-800 bg-amber-50 p-2 rounded border border-amber-200 mt-1">
                      <strong>Hukuki Dayanak:</strong> {activeTemplate.legalBasis}
                    </CardDescription>
                  )}
                </CardHeader>

                <CardContent className="p-6 space-y-4">
                  <div className="flex items-center justify-between">
                    <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                      Sözleşme / Onam Metni Önizlemesi
                    </h4>
                    <span className="text-[11px] text-slate-400">Dinamik HMK & TBK Parametreleri Enjekte Edilir</span>
                  </div>

                  <div className="bg-slate-50 border border-slate-200 rounded-lg p-4 font-mono text-xs text-slate-800 max-h-[460px] overflow-y-auto whitespace-pre-wrap leading-relaxed">
                    {activeTemplate.bodyTemplateMarkdown}
                  </div>

                  <div className="pt-3 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100">
                    <div className="flex items-center gap-2">
                      <a
                        href={`/api/legal/export/doc/${activeTemplate.id}`}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex"
                      >
                        <Button variant="outline" size="sm" className="text-xs gap-1.5 border-slate-300 text-slate-700 hover:bg-slate-100">
                          <Download className="w-3.5 h-3.5 text-blue-600" /> Düzenlenebilir Word (.doc) İndir
                        </Button>
                      </a>
                    </div>
                    <Button
                      onClick={() => setIsModalOpen(true)}
                      size="sm"
                      className="bg-emerald-600 hover:bg-emerald-700 text-white gap-1.5 shadow-sm"
                    >
                      <PenTool className="w-3.5 h-3.5" /> Canlı Rezervasyon & İmzala
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ) : (
              <div className="h-full flex items-center justify-center py-20 text-slate-400">
                Lütfen sol taraftan bir sözleşme veya onam formu seçiniz.
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Legal Gate Modal */}
      <LegalGateModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        bookingContext={sampleBookingContext}
        onAllContractsSigned={(results) => {
          setSignedResults(results);
          setIsModalOpen(false);
        }}
      />
    </div>
  );
};
